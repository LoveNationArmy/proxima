base = document.location.origin
randomId = () => (Math.random() * 10e6 | 0).toString(36) + (Math.random() * 10e6 | 0).toString(36)
randomNick = () => {
  const nicks = [
    'john', 'anna', 'bob', 'suzanne', 'joe', 'mary', 'phil', 'julia', 'george', 'kate', 'chris', 'christine'
  ]
  return nicks[Math.random() * nicks.length | 0]
}
cid = randomId() // client id
query = Object.fromEntries(new URLSearchParams(document.location.search))
chat = new Set(localStorage.chat ? localStorage.chat.split('\r\n') : [])
meta = new Set([`${cid}#0#nick,${query.nick === 'rand' ? randomNick() : query.nick || randomNick() }`])
offers = []
visitedOffers = []
channels = []
peers = []
json = res => res.json()
get = (a, b) => fetch(a, b).then(json)
del = (a) => fetch(a, { method: 'DELETE' })
post = (a, data) => {
  const body = new FormData()
  Object.keys(data).forEach(key => body.append(key, data[key]))
  return get(a, { method: 'POST', body })
}
waitFor = (n) => () => new Promise(resolve => setTimeout(resolve, n * 1000))
ownOffer = {}
broadcast = msg => channels.forEach(c => c.send(`${cid}\t${msg}`))
createPeer = () => new RTCPeerConnection({
  iceServers: []
  // iceServers: [{ urls: 'stun:stun.l.google.com:19302'}]
})
setupChannel = ({ channel }) => {
  channel.data = new Set
  return new Promise(resolve => {
    channel.onopen = () => {
      channels.push(channel)
      if (localStorage.chat) {
        channel.send(`${cid}\t${localStorage.chat}`)
      }
      channel.send(`${cid}\t\0${[...meta].join('\n')}`)
      render()
      resolve()
    }
    channel.onclose = () => {
      channels.splice(channels.indexOf(channel), 1)
      peers.splice(peers.indexOf(channel.peer), 1)
      connect()
    }
    channel.onmessage = async ({ data }) => {
      [path, data] = data.split('\t')
      path = `${path}:${channel.peer.cid}`
      // have we already received this data?
      if (!channel.data.has(data)) {
        // console.log('new data', path, data)
        channel.data.add(data)
        if (data[0] === '\0') { // data is meta
          const [parts, ...values] = data.slice(1).split(',')
          const [pcid, time, type] = parts.split('#')
          console.log('got', pcid, time, type, values)
          if (type === 'offer') {
            if (values[0] === cid) {
              console.log(cid, 'received offer from', pcid)
              const [peer, d] = await createRtcAnswer(values.slice(1).join(',')) // create rtc answer
              try {
                console.log('broadcasting answer', d)
                peer.cid = pcid
                broadcast(`\0${cid}#${Date.now()}#answer,${pcid},${d}`)
                await peer.connect()
                peers.push(peer)
                console.log('connection established by answer', peer)
              } catch (error) {
                console.error(error)
                peer.close()
              }
            }
          } else if (type === 'answer') {
            if (values[0] === cid) {
              console.log(cid, 'received answer from', pcid)
              const { peer } = peerOffer
              try {
                console.log('processing answer', values[1])
                const rd = JSON.parse(values.slice(1).join(','))
                peer.setRemoteDescription(rd[0])
                for (var i = 1; i < rd.length; i++) {
                  await peer.addIceCandidate(rd[i])
                }
                await setupChannel(peer)
                peers.push(peer)
                console.log('connection established by offer', peer)
              } catch (error) {
                peer.close()
                peerOffer = {}
              }
            }
          } else {
            data.slice(1).split('\n').forEach(msg => meta.add(msg))
          }
        } else { // data are chat
          data.split('\r\n').forEach(msg => chat.add(msg))
          localStorage.chat = [...chat].sort().join('\r\n')
        }
        render()
        // rebroadcast to peers
        channels.filter(other => other !== channel).forEach(other => {
          if (!other.data.has(data)) {
            other.data.add(data)
            other.send(`${path}\t${data}`)
          }
        })
      } else {
        // console.log('discarding', path, data)
      }
    }
  })
}
createRtcOffer = () => new Promise((resolve, reject) => {
  const data = []
  const peer = createPeer()

  peer.channel = peer.createDataChannel('chat')
  peer.channel.peer = peer

  let timeout

  peer.onnegotiationneeded = async e => {
    // console.log('on negotiation needed', e)
    const d = await peer.createOffer()
    // console.log('local description', d)
    await peer.setLocalDescription(d)
    timeout = setTimeout(() => {
      console.error('offer ice candidate missing, sending local description')
      resolve([peer, JSON.stringify([peer.localDescription, ...data])])
    }, 15000)
  }

  peer.onicecandidate = async e => {
    if (e.candidate) {
      data.push(e.candidate)
    } else {
      clearTimeout(timeout)
      resolve([peer, JSON.stringify([peer.localDescription, ...data])])
    }
  }
})
createRtcAnswer = async d => {
  const rd = JSON.parse(d)

  const data = []
  const peer = createPeer()

  let timeout

  peer.connect = () => new Promise((resolve, reject) => {
    const timeout = setTimeout(reject, 30000, new Error('Answer timed out.'))
    peer.ondatachannel = ({ channel }) => {
      clearTimeout(timeout)
      peer.channel = channel
      channel.peer = peer
      setupChannel({ channel }).then(resolve)
    }
  })

  promise = new Promise((resolve, reject) => {
    timeout = setTimeout(() => {
      console.error('answer ice candidate missing, sending local description')
      resolve([peer, JSON.stringify([peer.localDescription, ...data])])
    }, 20000)
    peer.oniceconnectionstatechange = ({ iceConnectionState: i }) =>
      i === 'disconnected' && reject()
    peer.onicecandidate = async e => {
      if (e.candidate) {
        data.push(e.candidate)
      } else {
        clearTimeout(timeout)
        resolve([peer, JSON.stringify([peer.localDescription, ...data])])
      }
    }
  })

  await peer.setRemoteDescription(rd[0])
  for (var i = 1; i < rd.length; i++) {
    await peer.addIceCandidate(rd[i])
  }
  ld = await peer.createAnswer()
  await peer.setLocalDescription(ld)

  return promise
}

log = pre => (...args) => args //(console.log(`${pre}:`, ...args), args)

notOwnOffer = offerId => offerId.split('.').pop() != cid
notKnownPeer = offerId => !peers.map(peer => peer.cid).includes(offerId.split('.').pop())
notVisitedOffer = offerId => !visitedOffers.includes(offerId)
getOffers = async () => log('offers')(offers = await get(base))
filterOffers = () => (offers = offers.filter(notOwnOffer).filter(notKnownPeer).filter(notVisitedOffer))
getNextOffer = async (offerId = offers.splice(Math.floor(Math.random() * offers.length), 1)[0]) => log('next offer')(offerId, await get(`${base}/?id=offers/${offerId}`))
// getNextOffer = async (offerId = offers.pop()) => log('next offer')(offerId, await get(`${base}/?id=offers/${offerId}`))
postOffer = async d => await post(base, { cid, d })
postAnswer = async (id, d) => {
  if (!notKnownPeer(id)) throw new Error('Answer aborted, known peer')
  await post(base, { id, cid, d })
}
getAnswer = async offerId => await get(`${base}/?id=answers/${offerId}`)
delOffer = async offerId => del(`${base}/?id=${offerId}`)

tryAnswer = async () => {
  filterOffers()
  if (!offers.length) {
    await getOffers()
  }
  filterOffers()
  if (!offers.length) {
    throw new Error('No offers to answer')
  }
  const [offerId, offer] = await getNextOffer()
  visitedOffers.push(offerId)
  const [peer, d] = await createRtcAnswer(offer.d) // create rtc answer
  try {
    // console.log('posting answer', offerId, d)
    await postAnswer(offerId, d)
    await peer.connect()
    peer.cid = offerId.split('.').pop()
    peers.push(peer)
    // console.log('connection established by answer', peer)
  } catch (error) {
    // console.error(error)
    peer.close()
    await delOffer(offerId)
  }
}

peerOffer = {}
offerToPeer = async (pcid) => {
  console.log('creating offer to', pcid)
  const [peer, d] = await createRtcOffer() // create rtc offer
  peerOffer.peer = peer
  peerOffer.peer.cid = pcid
  peerOffer.time = Date.now()
  const msg = `\0${cid}#${peerOffer.time}#offer,${pcid},${d}`
  console.log('broadcasting offer', msg)
  broadcast(msg)
}

tryOffer = async () => {
  if (ownOffer.id && !ownOffer.answered) {
    throw new Error('Trying offer when not answered yet')
  }
  // console.log('creating offer')
  const [peer, d] = await createRtcOffer() // create rtc offer
  try {
    const offer = ownOffer = await postOffer(d)
    // console.log('created offer', offer)
    let timedOut = false
    let rd, cid
    waitForAnswer = async () => {
      let answer
      try {
        // console.log('waiting for answer:', offer.id)
        answer = await getAnswer(offer.id)
      } catch {}
      if (answer) {
        if (!notKnownPeer(answer.cid)) throw new Error('Offer aborted, known peer')
        rd = JSON.parse(answer.d)
        cid = answer.cid
      } else {
        if (offer.timedOut) {
          // console.log('timed out, no answer')
          return
        }
        // console.log('no answer yet...')
        await ifPeersLessThan(maxNOfPeers).then(waitFor(randSecs())).then(waitForAnswer)
      }
    }
    await Promise.race([waitForAnswer(), waitFor(50)()])
    await delOffer(offer.id)
    if (!rd) {
      ownOffer.timedOut = true
      ownOffer = {}
      throw new Error('Offer timed out.')
    }
    // console.log('received answer', rd)
    peer.setRemoteDescription(rd[0])
    for (var i = 1; i < rd.length; i++) {
      await peer.addIceCandidate(rd[i])
    }
    await setupChannel(peer)
    peer.cid = cid
    peers.push(peer)
    // console.log('connection established by offer', peer)
    offer.answered = true
  } catch (error) {
    // console.error(error)
    peer.close()
    ownOffer = {}
    await delOffer(offer.id)
  }
}
maxNOfPeers = 6
repeatSecs = 10
randSecs = () => Math.random() * (1 + peers.length * 3)
ifPeersLessThan = (n) => peers.length < n ? Promise.resolve() : Promise.reject(new Error('Peers max'))
repeatOffer = () => tryOffer().finally(() => ifPeersLessThan(maxNOfPeers).then(waitFor(randSecs())).then(repeatOffer))
repeatAnswer = () => tryAnswer().finally(() => ifPeersLessThan(maxNOfPeers).then(waitFor(randSecs())).then(repeatAnswer))
connect = async () => {
  repeatOffer()
  await waitFor(randSecs())()
  repeatAnswer()
}
connect()
