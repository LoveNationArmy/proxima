import forEvent from './for-event.js'

const OPTIONS = {
  iceServers: []
}

export default class Peer {
  constructor (opts = OPTIONS) {
    this.connection = new RTCPeerConnection(opts)
    this.connected = false
    this.data = new Set
  }

  open (offer) {
    if (offer) {
      return this.createAnswer(offer)
    } else {
      return this.createOffer()
    }
  }

  close () {
    return this.connection.close()
  }

  async createOffer () {
    this.channel = this.connection.createDataChannel('data')
    await forEvent(this.connection, 'negotiationneeded')
    const offer = await this.connection.createOffer()
    return this.createSignal(offer)
  }

  async createAnswer (offer) {
    await this.connection.setRemoteDescription(offer)
    const answer = await this.connection.createAnswer()
    return this.createSignal(answer)
  }

  async createSignal (signal) {
    signal.sdp = signal.sdp.replace(/a=ice-options:trickle\s\n/g, '')
    await this.connection.setLocalDescription(signal)
    await Promise.race([
      forEvent(this.connection, 'icecandidate'),
      secs(30)
    ])
    return this.connection.localDescription
  }

  async connect (answer) {
    if (answer) {
      await this.connection.setRemoteDescription(answer)
    } else {
      this.channel = (await forEvent(this.connection, 'datachannel')).channel
    }
    return forEvent(this.channel, 'open')
  }
}
