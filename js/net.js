import { emit, once, on } from './lib/events.js'
import secs from './lib/secs.js'
import Peer from './peer.js'
import { encrypt } from './crypto.js'
import { formatter, parse, diff } from './parse.js'
import * as http from './signal-http.js'

const OPTIONS = {
  maxPeers: 6
}

export default class Net extends EventTarget {
  constructor (app, opts = OPTIONS) {
    super()
    this.cid = window.token.slice(-5)
    this.app = app
    this.opts = opts
    this.peers = []
    this.offerPeers = new Map()
    this.format = formatter(this.cid)
    this.notice = formatter('notice')
  }

  async connect () {
    this.make('offer')
    await secs() // shuffle entry for answers. useful when dev
    this.make('answer')
  }

  async addPeer (peer) {
    let snapshot

    // connect
    if (this.peers.find(p => p.cid === peer.cid)) {
      // already connected, drop peer
      console.warn(`Already connected with "${peer.cid}", dropping connection...`)
      peer.close()
      return
    }
    this.peers.push(peer)
    this.app.dispatch('connect', peer.cid)
    this.app.dispatch('join', [this.cid, peer.cid].sort().join())

    snapshot = this.app.state.merge(false, true)
    peer.send(snapshot, await parse(snapshot))

    emit(this, 'peer', peer)
    emit(this, `peer:${peer.cid}`, peer)

    // data in
    for await (const { detail: data } of on(peer, 'data', 'close')) {
      snapshot = this.app.state.merge(false, true)
      const chunk = diff(snapshot, data)
      peer.data.in = new Set([...peer.data.in, ...data])

      if (chunk.size) {
        parse(chunk, msg => this.app.handlers.handle({ ...msg, peer }))
          .then(async view => {
            if (view.data.size) {
              // console.log('broadcast:', view.data)
              this.broadcast(view.data, peer, await parse(new Set([...view.data, ...snapshot])))
            }

            emit(this, 'data', { data, view, peer }) // TODO: needed?
          })
      }
    }

    // disconnect
    this.peers.splice(this.peers.indexOf(peer), 1)
    this.app.dispatch('part', [this.cid, peer.cid].sort().join())
    this.app.dispatch('disconnect', peer.cid)
    emit(this, 'peer', peer)
  }

  async broadcast (data, peer = {}, view) {
    this.peers.filter(p => p.cid !== peer.cid).forEach(peer => peer.send(data, view))
  }

  async lessThanMaxPeers () {
    while (this.peers.length >= this.opts.maxPeers) {
      await once(this, 'peer')
    }
    return true
  }

  async make (type) {
    while (true) {
      await this.lessThanMaxPeers()
      await this.createPeer(type)
      await secs(2 + this.peers.length ** 3) // TODO: increase this in production
    }
  }

  async offerTo (cid) {
    this.app.state.channelView = [this.cid, cid].sort().join()

    if (this.peers.map(peer => peer.cid).includes(cid)) {
      console.warn(`Connection to ${cid} aborted, already connected.`)
      // this.state.channelView = cid
      // this.peers.find(peer => peer.cid === cid).send([this.format('syncme')], await parse(this.app.state.merge(false, true)))
      return
    }
    const peer = new Peer(this.app)
    const offer = await peer.createOffer()
    const encryptedOffer = await encrypt(
      JSON.parse(this.app.state.view.keys.get(cid)),
      JSON.stringify(offer.sdp)
    )
    this.offerPeers.set(cid, peer)
    this.app.dispatch(`offer:${cid}`, JSON.stringify(encryptedOffer))
    await Promise.race([once(peer, 'open'), secs(30)])
    try {
      if (!peer.connected) throw new Error(`Connection timeout [by offer to ${cid}].`)
      this.addPeer(peer)
      return
    } catch (error) {
      console.error(error)
    }
    peer.close()
  }

  async answerTo (offer) {
    const peer = new Peer(this.app)
    try {
      const answer = await peer.createAnswer(offer)
      const encryptedAnswer = await encrypt(
        JSON.parse(this.app.state.view.keys.get(offer.cid)),
        JSON.stringify(answer.sdp)
      )
      this.app.dispatch(`answer:${offer.cid}`, JSON.stringify(encryptedAnswer))
      await Promise.race([once(peer, 'open'), secs(30)])
      if (!peer.connected) throw new Error(`Connection timeout [by answer to ${offer.cid}].`)
      this.addPeer(peer)
      return
    } catch (error) {
      console.error(error)
    }
    peer.close()
  }

  async createPeer (type) {
    const peer = new Peer(this.app)
    try {
      if (type === 'offer') {
        const offer = await http.sendOffer(await peer.createOffer())
        const answer = await http.pollForAnswer(offer)
        await peer.receiveAnswer(answer)
      } else if (type === 'answer') {
        const known = this.peers.map(peer => peer.cid).join()
        const offer = await http.getNextOffer(known)
        const answer = await peer.createAnswer(offer)
        await http.sendAnswer(answer)
      }
      await Promise.race([once(peer, 'open'), secs(30)])
      if (!peer.connected) throw new Error(`Connection timeout [${type}].`)
      this.addPeer(peer)
      return
    } catch (error) {
      console.warn(error)
    }
    peer.close()
  }
        // public/private key generated on init
        // peer broadcasts public key
        // other peers use public key to encrypt a message(offer) to be whispered
        // only peer with private key can decrypt the message
        // channel.offer should contain an id known only to this peer
        // so that we can use it to delete the offer on completion/failure
        //await peer.connect(await this.handshake(transport, await peer.open()))

        // await this.handshake(transport, await peer.open(await this.handshake(transport)))
        // await peer.connect()

  // async handshake (transport, signal) {
  //   if (signal) {
  //     if (signal.type === 'offer') {
  //       const offer = await transport.sendOffer(signal)
  //       const answer = await transport.waitForAnswer(offer)
  //       return answer
  //     } else if (signal.type === 'answer') {
  //       await transport.sendAnswer(signal)
  //     }
  //   } else {
  //     const known = this.peers.map(peer => peer.cid).join()
  //     const offer = await transport.getNextOffer(known)
  //     return offer
  //   }
  // }

  // async swarmHandshake (signal) {
  //   if (signal) {
  //     if (signal.type === 'offer') {
  //       // use other public key to encrypt offer signal
  //       // broadcast encrypted offer signal to swarm
  //       // await for answer signal and examine unique id
  //       // decrypt answer signal using private key
  //       // return answer signal
  //     } else if (signal.type === 'answer') {
  //       // use other public key to encrypt answer signal
  //       // broadcast encrypted answer signal to swarm
  //     }
  //   } else {
  //     // get offer signal picked from message
  //     // decrypt offer signal using private key
  //     // return offer signal
  //   }
  // }
}

// loop: for await (const event of channel.event()) {
//   switch (event.type) {
//     case 'open':
//       //
//       break loop
//     case 'close':
//       //
//       break loop
//     case 'message':
//       //
//       break
//   }
// }
