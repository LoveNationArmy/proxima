import random from './lib/seedable-random.js'
import { decrypt } from './crypto.js'
import { parse } from './parse.js'
import { once } from './lib/events.js'

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

  async trackendanswer ({ peer, target, cid, text }) {
    if (target !== this.app.net.cid) return
    if (peer.cid !== cid) return

    const kind = text.split(' ')[0]
    text = text.split(' ').slice(1).join(' ')
    await peer.connection.setRemoteDescription(JSON.parse(text))
    peer.removeStream(kind)
  }

  async trackend ({ peer, target, cid, text }) {
    if (target !== this.app.net.cid) return
    if (peer.cid !== cid) return

    const kind = text.split(' ')[0]
    text = text.split(' ').slice(1).join(' ')
    await peer.connection.setRemoteDescription(JSON.parse(text))
    peer.removeStream(kind)
    const answer = await peer.connection.createAnswer()
    await peer.connection.setLocalDescription(answer)
    this.app.dispatch(`trackendanswer:${cid}`, kind, JSON.stringify(peer.connection.localDescription))
  }

  async trackoffer ({ peer, target, cid, text }) {
    if (target !== this.app.net.cid) return
    if (peer.cid !== cid) return

    const kind = text.split(' ')[0]
    text = text.split(' ').slice(1).join(' ')
    await peer.connection.setRemoteDescription(JSON.parse(text))
    await peer.addStream(kind)
    const answer = await peer.connection.createAnswer()
    await peer.connection.setLocalDescription(answer)
    this.app.dispatch(`trackanswer:${cid}`, JSON.stringify(peer.connection.localDescription))
  }

  async trackanswer ({ peer, target, cid, text }) {
    if (target !== this.app.net.cid) return
    if (peer.cid !== cid) return

    await peer.connection.setRemoteDescription(JSON.parse(text))
  }

  async syncme ({ peer }) {
    const snapshot = this.app.state.merge(false, true)
    peer.send(snapshot, await parse(snapshot))
    return false
  }
}
