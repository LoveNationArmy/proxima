// concept:
//
// data are stored as "lines" delimited by \r\n
//   time\tpath\tdata\r\n
//
// path are ids of clients in the order they arrived:
//   :sender:sender:sender
//
// same data are discarded.
//
// app data are always "views" derived from the "line" data
//
// this way we can always discard a user's activity
// completely using /block
// blocks are transmitted through the network
// peers that trust also automatically /block
//
// channels are basically collections of data and peers
// when joining a channel, you automatically /trust operators
// peers rebroadcast to other peers who have announced they
// have /join-ed channel in our channel meta information
//
// entry point is the #garden
// for a channel to be /join-able an operator of the channel
// must be in #garden. they get notifications for peers
// that want to join and manage the negotiation
// with the other peers in the channel. peers cannot
// join a channel without the negotiation of an operator.
// an operator can choose to automatically accept offers
// otherwise can accept the 'direct' communication handshake
// and examine the peer first.
// /join requests are rtc offers that are sent to all known operators
// that reside in #garden for that channel. operators announce their
// channels. when an operator establishes a direct communication
// with a peer, they can announce their cid and other meta data.
//
// a compromised channel can be retrieved by /mutiny
// /mutiny informs other peers that you wish to be /trust-ed in that channel
// trusting a mutiny user automatically untrusts other operators
// the channel eventually "forks" away as noone trusts the past operators

import randomId from './lib/random-id.js'

export default class Controller extends EventTarget {
  constructor (state) {
    this.state = state
  }

  dispatch (message) {
    const msg = [
      performance.timeOrigin + performance.now(),
      this.state.cid,
      randomId(),
      message
    ].join('\t')
    this.state.data.push(msg)
    this.dispatchEvent(new CustomEvent('data', { detail: data }))
  }
}
