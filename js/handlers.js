import random from './lib/seedable-random.js'
import { decrypt } from './crypto.js'
import { parse } from './parse.js'

export default class Handlers {
  constructor (app) {
    this.app = app
  }

  handle (data) {
    if (this[data.command]) return this[data.command](data)
  }

  async join ({ cid, peer }) {
    if (peer.cid === cid) {
      const snapshot = this.app.state.merge(false, true)
      peer.send(snapshot, await parse(snapshot))
    }
  }

  async offer ({ target, cid, text }) {
    if (target !== this.app.net.cid) return

    const decryptedOffer = await decrypt(this.app.keys.privateKey, JSON.parse(text))
    this.app.net.answerTo({ cid, type: 'offer', sdp: JSON.parse(decryptedOffer) })
    return false
  }

  async answer ({ target, cid, text }) {
    if (target !== this.app.net.cid) return

    const offerPeer = this.app.net.offerPeers.get(cid)
    if (offerPeer) {
      const decryptedAnswer = await decrypt(this.app.keys.privateKey, JSON.parse(text))
      await offerPeer.receiveAnswer({ cid, type: 'answer', sdp: JSON.parse(decryptedAnswer) })
      this.app.net.offerPeers.delete(cid) // ensure this is not used for double answers
    } else {
      console.error('No such offer peer (double answer attempt?):', answer)
    }
    return false
  }

  async syncme ({ peer }) {
    const snapshot = this.app.state.merge(false, true)
    peer.send(snapshot, await parse(snapshot))
    return false
  }
}
