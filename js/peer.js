import { emit, once, on } from './lib/events.js'
import secs from './lib/secs.js'
import copy from './lib/copy.js'

const OPTIONS = {
  iceServers: []
}

export default class Peer extends EventTarget {
  constructor (opts = OPTIONS) {
    super()
    this.cid = null
    this.connected = false
    this.connection = new RTCPeerConnection(opts)
  }

  close () {
    this.connected = false
    this.connection.close()
    if (this.data) this.data.end()
    emit(this, 'close')
  }

  async createOffer () {
    this.openChannel(this.connection.createDataChannel('data'))
    await once(this.connection, 'negotiationneeded')
    const offer = await this.connection.createOffer()
    return this.createSignal(offer)
  }

  receiveAnswer (answer) {
    this.cid = answer.cid
    return this.connection.setRemoteDescription(answer)
  }

  async createAnswer (offer) {
    this.cid = offer.cid
    await this.connection.setRemoteDescription(offer)
    const answer = await this.connection.createAnswer()
    once(this.connection, 'datachannel').then(({ channel }) => this.openChannel(channel))
    return this.createSignal({ ...offer, ...copy(answer) })
  }

  async createSignal (signal) {
    signal.sdp = signal.sdp.replace(/a=ice-options:trickle\s\n/g, '')
    await this.connection.setLocalDescription(signal)
    await Promise.race([once(this.connection, 'icecandidate'), secs(30)])
    return { ...signal, ...copy(this.connection.localDescription) }
  }

  async openChannel (channel) {
    this.channel = channel
    this.data = on(this.channel, 'message', 'close')
    await once(this.channel, 'open')
    this.connected = true
    emit(this, 'open')
    await once(this.channel, 'close')
    this.close()
  }
}
