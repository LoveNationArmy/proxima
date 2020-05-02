import $ from './lib/element.js'
import morphdom from './lib/morphdom.js'
import randomId from './lib/random-id.js'
import State from './state.js'
import Net from './net.js'

export default class App {
  constructor (el) {
    this.el = el
    this.app = this
    this.net = new Net(this)
    this.state = new State(this, this.load())
    this.ui = $(UI, this)
    this.net.addEventListener('peer', () => this.render())
    this.net.addEventListener('data', () => this.render())
    document.addEventListener('render', () => this.render())
  }

  dispatch (...message) {
    message = this.net.format(...message)
    console.log('dispatch', message)
    this.state.data.add(message)
    this.net.broadcast([message], this.net)
    // this.dispatchEvent(new CustomEvent('data', { detail: data }))
  }

  load () {
    return localStorage.data || ''
  }

  save () {
    localStorage.data = [...this.state.data].join('\r\n')
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
    console.log(this.state.data, this.net.peers)
    const html = this.ui.toString(true)
    morphdom(this.el, html, {
      onNodeAdded: this.onrender,
      onElUpdated: this.onrender
    })
  }
}

class UI {
  template () {
    const view = this.state.view
    const channel = view.channels.get('#garden')
    return `
      <div class="app">
        <div class="main">
          ${ $(ChatArea, { channel: view.channels.get('#garden'), view, app: this.app, state: this.state }) }
        </div>
        <div class="side">
          <div class="peers">
            ${ channel ? $.map([...channel.users], cid => `<div>${view.nicks.get(cid) || cid}</div>`) : '' }
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
          ${ this.channel ? $.map(this.channel.wall, msg => `<div>${this.view.nicks.get(msg.cid) || msg.cid}: ${msg.text}</div>`) : ''}
        </div>
        <div class="chatbar">
          <div class="nick">${ this.view.nicks.get(this.app.net.cid) }</div>
          <textarea
            class="${ $.class({ pre: this.state.textareaRows > 1 }) }"
            onkeydown="${ this.processKeyDown }(event)"
            oninput="${ this.processInput }()"
            rows=${ this.state.textareaRows }>${ this.state.newPost }</textarea>
          <button onclick="${ this.createPost }()">send</button>
        </div>
      </div>
    `
  }

  createPost () {
    if (!this.state.newPost.length) return
    this.app.dispatch('msg:#garden', this.state.newPost)
    this.state.newPost = ''
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
    this.el.parentNode.scrollTop = this.el.parentNode.scrollHeight
    if (newRows === rows) return false
    this.state.textareaRows = newRows
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