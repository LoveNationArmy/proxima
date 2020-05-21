import $ from './lib/element.js'
import dom from './lib/dom.js'
import randomId from './lib/random-id.js'
import Handlers from './handlers.js'
import State from './state.js'
import Net from './net.js'
import { formatter, parse } from './parse.js'
import { generateKeyPair } from './crypto.js'
import { once } from './lib/events.js'

export default class App {
  constructor (el) {
    this.el = el
    this.app = this
  }

  async start () {
    this.net = new Net(this)
    this.keys = await generateKeyPair()
    this.state = new State(this, this.load())
    this.handlers = new Handlers(this)
    this.notice = formatter('notice')
    this.ui = $(UI, this)
    this.net.addEventListener('peer', () => this.render())
    this.net.addEventListener('data', () => this.render())
    document.addEventListener('render', () => this.render())
    this.net.connect()
    this.render()
  }

  async dispatch (...message) {
    message = this.net.format(...message)
    // console.log('dispatch', message)
    this.state.data.add(message)
    this.net.broadcast([message], this.net, await parse(this.state.merge(false, true)))
    // this.dispatchEvent(new CustomEvent('data', { detail: data }))
  }

  load () {
    return localStorage.data || ''
  }

  save () {
    localStorage.data = [...this.state.data].join('\r\n')
  }

  offerTo (cid) {
    this.net.offerTo(cid)
  }

  async render () {
    this.state.view = await parse(this.state.merge(true))
    const html = this.ui.toString(true)
    dom(this.el, html, { trim: true })
  }
}

class UI {
  constructor () {
    this.isBottom = true
  }

  template () {
    const view = this.state.view
    const channels = view.channels.keys()
    const channel = view.channels.get(this.state.channelView)
    const peers = this.app.net.peers.map(peer => peer.cid)
    prevUser = null
    return `
      <div class="app">
        <div class="side">
          <div class="channels">
            ${ $.map([...channels].filter(c => c[0] === '#'), c => `
              <div
                class="channel ${ $.class({
                  active: c === this.state.channelView,
                  dim: !view.channel(c).users.has(this.app.net.cid)
                }) }"
                onclick="${ this.switchToChannel }('${c}')">${c[0] === '#' ? c : c.split(',').map(cid => view.nicks.get(cid)).join()}</div>
            `)}
          </div>
          <div class="peers">
            ${ channel ? $.map([...channel.users].filter(cid => cid !== this.app.net.cid), cid => `
              <div
                class="peer ${ $.class({
                  active: this.state.channelView.split(',').includes(cid),
                  dim: !peers.includes(cid)
                }) }"
                onclick="${ this.offerTo }('${cid}')">
                ${view.nicks.get(cid) || cid}
              </div>
              `) : '' }
          </div>
        </div>
        <div class="main" onscroll="${ this.checkScrollBottom }()" onupdate="${ this.scrollToBottom }()">
          ${ $(ChatArea, { view, target: this.state.channelView, app: this.app, state: this.state }) }
        </div>
      </div>
    `
  }

  switchToChannel (channel) {
    this.state.channelView = channel
  }

  checkScrollBottom () {
    this.isBottom = Math.round(this.scrollTop + this.clientHeight) >= this.scrollHeight - 50
    return false
  }

  scrollToBottom () {
    if (this.isBottom) this.scrollTop = this.scrollHeight
    return false
  }
}

class ChatArea {
  template () {
    const view = this.view
    const channel = this.view.channels.get(this.target)
    const peerCid = this.target.split(',').find(cid => cid !== this.app.net.cid)
    const peer = this.app.net.peers.find(peer => peer.cid === peerCid)
    return channel ? `
      <div class="chatarea">
        <div class="wall">
          ${ peer && peer.localStream ? `<div class="streams">` : ''}
          ${ peer && peer.localStream ? `<video id="localVideo" onupdate="${ this.setStream }('local')" autoplay playsinline style="transform: scaleX(-1)"></video>` : '' }
          ${ peer && peer.remoteStream ? `<video id="remoteVideo" onupdate="${ this.setStream }('remote')" autoplay playsinline ${peer.remoteStream.getAudioTracks().length ? 'controls' : ''}></video>` : '' }
          ${ peer && peer.localStream ? `</div>` : ''}
          ${ $.map(channel.wall, post => $(Post, post, { view, channel })) }
        </div>
        <div class="chatbar">
          <div class="target">${this.app.net.cid}</div>
          <div class="nick">${ view.nicks.get(this.app.net.cid) }</div>
          <textarea
            class="${ $.class({ pre: this.state.textareaRows > 1 }) }"
            onkeydown="${ this.processKeyDown }(event)"
            oninput="${ this.processInput }()"
            rows=${ this.state.textareaRows }></textarea>
          <button onclick="${ this.createPost }()">send</button>
          ${ peer ? `
            <button onclick="${ this.toggleStream }('video')" class="${ $.class({
              active: (peer.localStream && peer.localStream.getVideoTracks().length)
                   || (peer.remoteStream && peer.remoteStream.getVideoTracks().length)
              }) }">📹</button>
            <button onclick="${ this.toggleStream }('audio')" class="${ $.class({
              active: (peer.localStream && peer.localStream.getAudioTracks().length)
                   || (peer.remoteStream && peer.remoteStream.getAudioTracks().length)
              }) }">🎙️</button>
          ` : '' }
          <div class="target">${this.target[0] === '#' ? this.target : view.nicks.get(this.target.split(',').find(cid => cid !== this.app.net.cid))}</div>
        </div>
      </div>
    ` : ''
  }

  setStream (kind) {
    const peerCid = this.target.split(',').find(cid => cid !== this.app.net.cid)
    const peer = this.app.net.peers.find(peer => peer.cid === peerCid)
    this.muted = null
    this.muted = kind === 'local'
    this.controls = null
    this.controls = kind === 'remote' && peer.remoteStream.getAudioTracks().length > 0
    this.srcObject = null
    this.srcObject = peer[kind + 'Stream']
    return false
  }

  async toggleStream (kind) {
    const peerCid = this.target.split(',').find(cid => cid !== this.app.net.cid)
    const peer = this.app.net.peers.find(peer => peer.cid === peerCid)

    if (peer.localStream && peer.localStream.getTracks().filter(track => track.kind === kind).length) {
      peer.removeStream(kind)
      // await once(peer.connection, 'negotiationneeded')
      const offer = await peer.connection.createOffer()
      await peer.connection.setLocalDescription(offer)
      this.app.dispatch(`trackend:${peerCid}`, kind, JSON.stringify(peer.connection.localDescription))
    } else {
      await peer.addStream(kind)
      // await once(peer.connection, 'negotiationneeded')
      const offer = await peer.connection.createOffer()
      await peer.connection.setLocalDescription(offer)
      this.app.dispatch(`trackoffer:${peerCid}`, kind, JSON.stringify(peer.connection.localDescription))
    }
  }

  createPost () {
    if (!this.state.newPost.length) return
    if (this.state.newPost[0] === '/') {
      const command = this.state.newPost.slice(1).split(' ')[0]
      const value = this.state.newPost.split(' ').slice(1).join(' ')
      this.app.dispatch(this.state.newPost.slice(1))
      if (command === 'join') {
        this.state.channelView = value
      }
    } else {
      this.app.dispatch(`msg:${this.state.channelView}`, this.state.newPost)
    }
    this.state.newPost = this.value = ''
    this.state.textareaRows = 1
  }

  processKeyDown (event) {
    if (event.which === 13) {
      if (event.ctrlKey === true) {
        const pos = this.selectionStart
        this.value = this.value.slice(0, pos) + '\n' + this.value.slice(pos)
        this.processInput()
        this.selectionStart = this.selectionEnd = pos + 1
      } else {
        event.preventDefault()
        this.createPost()
        return false
      }
    } else {
      return false
    }
  }

  processInput (arg) {
    const rows = this.state.textareaRows
    this.state.newPost = this.value
    const computed = window.getComputedStyle(this.el)
    const newRows = Math.max(
      this.state.newPost.split('\n').length,
      Math.floor(this.scrollHeight / (parseFloat(computed.lineHeight)))
    )
    if (newRows === rows) return false
    this.state.textareaRows = newRows
  }
}

let prevUser, prevTime

class Post {
  // ({ meta, user, time, text, replies = [] }) => `
  template () {
    const lastPrevUser = prevUser
    const lastPrevTime = lastPrevUser !== this.cid ? 0 : prevTime
    prevUser = this.cid
    prevTime = parseInt(this.time)
    return `
      <br>
      <div class="post ${ $.class({ dim: !this.channel.users.has(this.cid) }) }">
        ${ lastPrevUser !== this.cid ? `<a class="user" href="/#~${this.cid}">${htmlescape(this.view.nicks.get(this.cid))}:</a>` : `` }
        ${ prevTime - lastPrevTime > 1000 * 60 ? `
        <info>
          <!-- <time>${new Date(+this.time).toLocaleString()}</time> -->
          <a href="#">reply</a>
        </info>` : '' }
        <p
          class="${ $.class({ pre: this.text.includes('\n') }) }"
          >${htmlescape(this.text, this.text.includes('\n'))}</p>
        ${ $.map(this.replies || [], post => $(Post, { view: this.view, ...post })) }
      </div>
    `
  }
}

function htmlescape (text = '', initialSpace = false) {
  text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;')
  if (initialSpace) text = text.replace(/ /,'&nbsp;')
  return text
}

        // ${ this.privateOpen ? `
        //   <div class="private">
        //     ${ $(ChatArea, this.private[this.privatePeer.cid]) }
        //   </div>
        // ` : `` }

            // ${ $.map(this.app.net.peers.map(peer => [peer.cid, this.meta.getUser(peer.cid)]).concat(Object.keys(this.meta.nicks)
            //   .filter(pcid => !this.app.net.peers.map(peer => peer.cid).includes(pcid) && pcid !== cid)
            //   .map(pcid => [pcid, this.meta.getUser(pcid), true]))
            //   .sort((a, b) => a[1] > b[1] ? 1 : a[1] < b[1] ? -1 : 0)
            // , ([pcid, nick, inNetwork]) =>
            //   `<div class="peer ${inNetwork ? 'in-network' : ''}" ${inNetwork ? `data-cid="${pcid}" onclick="${ this.offerToPeer }(this.dataset.cid)"` : ''}>${htmlescape(nick)}</div>`) }