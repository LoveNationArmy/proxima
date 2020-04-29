import forEvent from './lib/for-event.js'
import secs from './lib/secs.js'
import Peer from './peer.js'
import * as serverSignal from './server-signal.js'

const OPTIONS = {
  maxPeers: 5
}

export default class Net extends EventTarget {
  constructor (opts = OPTIONS) {
    super()
    this.opts = opts
    this.peers = []
    this.rooms = new Map()
    this.data = new Set()
  }

  connect () {
    this.makeOffers()
    this.makeAnswers()
  }

  async addPeer (peer) {
    peer.connected = true
    this.peers.push(peer)
    this.dispatchEvent(new CustomEvent('peer', { detail: { status: 'add', peer } }))
    await forEvent(peer.channel, 'close')
    peer.connected = false
    this.peers.splice(this.peers.indexOf(peer), 1)
    this.dispatchEvent(new CustomEvent('peer', { detail: { status: 'remove', peer } }))
  }

  async listen (peer) {
    while (peer.connected) {
      // wait for a message from channel
      var { data } = await forEvent(peer.channel, 'message')

      // :abcd:efgh:ijkl data

      // separate path & data
      // path is client ids from each hop:
      //   :abcd:efgh:ijkl
      // data is an entire chunk of data, which may contain
      // multiple messages delimited with \r\n
      var [path, data] = parse(data)

      // if we have already processed this data, continue
      if (this.data.has(data)) continue

      // data is new, remember them
      this.data.add(data)
      this.peer.data.add(data)

      // separate type and data
      var [type, data] = parse(data)

      data = data.split('\r\n')

      switch (type) {
        case 'meta':
          data.forEach(item => {
            var [type, meta] = parse(item)
            switch (type) {
              case 'nick':
                // TODO: prevent bad actors
                var [cid, nick] = parse(meta)
                this.meta.nicks.set(cid, nick)
                break
            }
          })
          break
        case 'message':
          data.forEach(item => {
            var [room, message] = parse(item)
            if (!this.rooms.has(room)) {
              this.rooms.set(room, new Set())
            }
            this.rooms.get(room).add(message)
          })
          break
        case 'handshake':
          break
      }

      this.dispatchEvent(new CustomEvent('message', { detail: { }}))
    }
  }

  async lessThanMaxPeers () {
    while (this.peers.length >= this.opts.maxPeers) {
      await forEvent(this, 'peer')
    }
    return true
  }

  async makeOffers () {
    do {
      try {
        this.addPeer(await this.createPeer('offer', this.serverHandshake))
      } catch (error) {
        console.error(error)
      }
    } while (await this.lessThanMaxPeers() && await secs(3 + peers.length ** 5))
  }

  async makeAnswers () {
    do {
      try {
        this.addPeer(await this.createPeer('answer', this.serverHandshake))
      } catch (error) {
        console.error(error)
      }
    } while (await this.lessThanMaxPeers() && await secs(3 + peers.length ** 5))
  }

  async createPeer (type, handshake) {
    const peer = new Peer()
    try {
      switch (type) {
        case 'offer':
          // public/private key generated on init
          // peer broadcasts public key
          // other peers use public key to encrypt a message(offer) to be whispered
          // only peer with private key can decrypt the message
          // channel.offer should contain an id known only to this peer
          // so that we can use it to delete the offer on completion/failure
          await peer.connect(await handshake(await peer.open()))
          break

        case 'answer':
          await handshake(await peer.open(await handshake()))
          await peer.connect()
          break
      }
      return peer
    } catch (error) {
      peer.close()
      throw error
    }
  }

  async serverHandshake (signal) {
    if (signal) {
      switch (signal.type) {
        case 'offer':
          const offer = await serverSignal.sendOffer(signal)
          const answer = await serverSignal.waitForAnswer(offer)
          return answer
          break

        case 'answer':
          await serverSignal.sendAnswer(signal)
          break
      }
    } else {
      const offer = await serverSignal.getNextOffer()
      return offer
    }
  }

  async swarmHandshake (signal) {
    if (signal) {
      switch (signal.type) {
        case 'offer':
          // use other public key to encrypt offer signal
          // broadcast encrypted offer signal to swarm
          // await for answer signal and examine unique id
          // decrypt answer signal using private key
          // return answer signal
          break
        case 'answer':
          // use other public key to encrypt answer signal
          // broadcast encrypted answer signal to swarm
          break
      }
    } else {
      // get offer signal picked from message
      // decrypt offer signal using private key
      // return offer signal
    }
  }
}

function parse (data, times = 1) {
  const parts = data.split('\t')
  return parts.slice(0, times).concat(parts.slice(times).join('\t'))
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
