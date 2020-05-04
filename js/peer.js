import { emit, once, on } from './lib/events.js'
import secs from './lib/secs.js'
import copy from './lib/copy.js'
import { formatter, lines, diff, parseLine } from './parse.js'

const OPTIONS = {
  iceServers: []
}

export default class Peer extends EventTarget {
  constructor (opts = OPTIONS) {
    super()
    this.cid = null
    this.data = { in: new Set(), out: new Set() }
    this.connected = false
    this.connection = new RTCPeerConnection(opts)
    this.connection.onconnectionstatechange = () => {
      switch (this.connection.connectionState) {
        case 'disconnected':
        case 'failed':
        case 'closed':
          this.close()
      }
    }
  }

  close () {
    this.connected = false
    try { this.connection.close() } catch {}
    try { this.messages.end() } catch {}
    emit(this, 'close')
  }

  send (data, view) {
    let chunk = new Set([
      ...diff(this.data.in, data),
      ...diff(this.data.out, data)
    ])
    if (chunk.size) {
      let set = new Set()

      // don't share message if user does not belong to channel
      for (const line of chunk.values()) {
        const msg = parseLine(line)
        if (msg.command === 'msg' && msg.param[0] === '#' && !view.channel(msg.param).users.has(this.cid)) {
          continue
        } else {
          set.add(line)
        }
      }

      if (set.size) {
        this.data.out = new Set([...this.data.out, ...set])
        try { this.channel.send([...set].join('\r\n')) } catch {}
      }
    }
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
    this.messages = on(this.channel, 'message', 'close')
    await once(this.channel, 'open')
    this.format = formatter(this.cid)
    this.connected = true
    emit(this, 'open')

    // data in
    for await (const { data } of this.messages) {
      const chunk = new Set([
        ...diff(this.data.in, [data]),
        ...diff(this.data.out, [data])
      ])
      if (chunk.size) {
        emit(this, 'data', chunk)
      }
    }

    this.close()
  }
}
