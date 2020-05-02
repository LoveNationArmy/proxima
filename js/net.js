import { emit, once, on } from './lib/events.js'
import secs from './lib/secs.js'
import Peer from './peer.js'
import * as http from './signal-http.js'

const OPTIONS = {
  maxPeers: 6
}

export default class Net extends EventTarget {
  constructor (opts = OPTIONS) {
    super()
    this.opts = opts
    this.peers = []
  }

  async connect () {
    this.make('offer')
    await secs() // shuffle entry for answers. useful when dev
    this.make('answer')
  }

  async addPeer (peer) {
    this.peers.push(peer)
    emit(this, 'peer', peer)
    this.listen(peer)

    peer.channel.send(`${Date.now()}\t${this.cid}\t${Math.random()}\t/msg #garden hello`)

    await once(peer, 'close')

    this.peers.splice(this.peers.indexOf(peer), 1)
    emit(this, 'peer', peer)
  }

  async listen (peer) {
    for await (const { detail: data } of peer.data) {
      emit(this, 'data', { data, peer })
    }
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
      await secs(3 + this.peers.length ** 3)
    }
  }

  async createPeer (type) {
    const peer = new Peer()
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
    } catch (error) {
      console.error(error)
      peer.close()
    }
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
