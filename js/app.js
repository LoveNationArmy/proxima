import $ from './lib/element.js'
import morphdom from './lib/morphdom.js'
import randomId from './lib/random-id.js'
import State from './state.js'
import Net from './net.js'

export default class App {
  constructor (el) {
    this.el = el
    this.setState(new State(this.load()))
    this.net = new Net()
    this.net.cid = this.state.cid
    this.state.peers = this.net.peers
    this.net.addEventListener('peer', () => this.render())
    this.net.addEventListener('message', ({ detail: { data, peer } }) => {
      this.state.data.push(data + ' from ' + peer.cid)
      this.render()
    })
    document.addEventListener('render', () => this.render())
  }

  setState (state) {
    this.state = state
    this.ui = $(UI, this.state, { app: this })
  }

  dispatch (message) {
    const msg = [
      performance.timeOrigin + performance.now(),
      this.state.cid,
      randomId(),
      message
    ].join('\t')
    this.state.data.push(msg)
    this.net.peers.forEach(peer => peer.channel.send(msg))
    // this.dispatchEvent(new CustomEvent('data', { detail: data }))
  }

  load () {
    return localStorage.data || ''
  }

  save () {
    localStorage.data = this.state.data.join('\r\n')
  }

  onrender (el) {
    if (el instanceof Element) {
      const expr = el.getAttribute('onrender')
      if (expr) {
        const fn = new Function(expr)
        fn.call(el)
      }
    }
  }

  render () {
    console.log('render', this.state)
    const html = this.ui.toString(true)
    morphdom(this.el, html, {
      onNodeAdded: this.onrender,
      onElUpdated: this.onrender
    })
  }
}

class UI {
  template () {
    return `
      <div class="app">
        <div class="main">
          ${ $(ChatArea, this.ref, { app: this.app }) }
        </div>
        <div class="side">
          <div class="peers">
            ${ $.map(this.peers, peer => `<div>${peer.cid}</div>`) }
          </div>
        </div>
      </div>
    `
  }
}

class ChatArea {
  template () {
    return `
      <div class="chatarea">
        <div class="wall">
          ${ $.map(this.wall, msg => `<div>${msg.text}</div>`) }
        </div>
        <div class="chatbar">
          <textarea
            class="${ $.class({ pre: this.textareaRows > 1 }) }"
            onkeydown="${ this.processKeyDown }(event)"
            oninput="${ this.processInput }()"
            onrender="this.focus()"
            rows=${ this.textareaRows }>${ this.newPost }</textarea>
          <button onclick="${ this.createPost }()">send</button>
        </div>
      </div>
    `
  }

  createPost () {
    if (!this.newPost.length) return
    this.app.dispatch(this.newPost)
    this.newPost = ''
  }

  processKeyDown (event) {
    if (event.which === 13) {
      if (event.ctrlKey === true) {
        this.value += '\n'
        this.processInput()
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
    const rows = this.textareaRows
    const newRows = this.value.split('\n').length
    this.newPost = this.value
    this.el.scrollTop = this.el.scrollHeight
    if (rows === newRows) return false
  }
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