import Peer from './peer.js'
import * as serverSignal from './server-signal.js'

export default function connect () {
  makeOffers()
  makeAnswers()
}

async function makeOffers () {
  do {
    try {
      net.addPeer(await createPeer('offer', serverHandshake))
    } catch (error) {
      console.error(error)
    }
  } while (await lessThanMaxPeers() && await secs(3 + peers.length ** 5))
}

async function makeAnswers () {
  do {
    try {
      net.addPeer(await createPeer('answer', serverHandshake))
    } catch (error) {
      console.error(error)
    }
  } while (await lessThanMaxPeers() && await secs(3 + peers.length ** 5))
}

async function createPeer (type, handshake) {
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

async function serverHandshake (signal) {
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

async function swarmHandshake (signal) {
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
