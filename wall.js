// import { $ } from './element.js'
// import morphdom from './vdiff.js'

class App {
  template () {
    // return ({ wall, meta, textareaRows, newPost }, { createPost, offerToPeer, processKeyDown, processInput }) => `
    return `
      <div class="app">
        <div class="main">
          ${ $(ChatArea, state) }
        </div>
        ${ this.privateOpen ? `
          <div class="private">
            ${ $(ChatArea, this.private[this.privatePeer.cid]) }
          </div>
        ` : `` }
        <div class="side">
          <div class="peers">
            ${ $.map(peers.map(peer => [peer.cid, this.meta.getUser(peer.cid)]).concat(Object.keys(this.meta.nicks)
              .filter(pcid => !peers.map(peer => peer.cid).includes(pcid) && pcid !== cid)
              .map(pcid => [pcid, this.meta.getUser(pcid), true]))
              .sort((a, b) => a[1] > b[1] ? 1 : a[1] < b[1] ? -1 : 0)
            , ([pcid, nick, inNetwork]) =>
              `<div class="peer ${inNetwork ? 'in-network' : ''}" ${inNetwork ? `data-cid="${pcid}" onclick="${ this.offerToPeer }(this.dataset.cid)"` : ''}>${htmlescape(nick)}</div>`) }
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
          ${ $.map(this.wall, post => $(Post, { meta: this.meta, ...post })) }
        </div>
        <div class="chatbar">
          <div class="nick">${ this.meta.nicks[cid] }</div>
          <textarea
            onrender="this.focus()"
            class="${ $.class({ pre: this.textareaRows > 1 }) }"
            onkeydown="${ this.processKeyDown }(event)"
            oninput="${ this.processInput }()"
            rows=${ this.textareaRows }>${ this.newPost }</textarea>
          <button onclick="${ this.createPost }()">send</button>
        </div>
      </div>
    `
  }

  createPost () {
    if (!this.newPost.length) return
    const id = randomId()
    const post = `${cid}#${id}#${Date.now()},${this.newPost}`
    chat.add(post)
    localStorage.chat = [...chat].sort().join('\r\n')
    broadcast(post)
    // this.textareaRows = 1
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
      }
    } else {
      return false
    }
  }

  processInput (arg) {
    const rows = this.textareaRows
    const newRows = this.value.split('\n').length
    this.newPost = this.value
    scrollEl.scrollTop = scrollEl.scrollHeight
    if (rows === newRows) return false
  }
}

let prevUser = null
let prevTime = 0

class Post {
  // ({ meta, user, time, text, replies = [] }) => `
  template () {
    const lastPrevUser = prevUser
    const lastPrevTime = lastPrevUser !== this.user ? 0 : prevTime
    prevUser = this.user
    prevTime = +this.time
    return `
      <br>
      <div class="post">
        ${ lastPrevUser !== this.user ? `<a class="user" href="/#~${this.user}">${htmlescape(this.meta.getUser(this.user))}:</a>` : `` }
        ${ this.time - lastPrevTime > 1000 * 60 ? `
        <info>
          <!-- <time>${new Date(+this.time).toLocaleString()}</time> -->
          <a href="#">reply</a>
        </info>` : '' }
        <p class="${ this.text.join(',').includes('\n') ? 'pre' : '' }">${htmlescape(this.text.join(','), this.text.join(',').includes('\n'))}</p>
        ${ $.map(this.replies || [], post => $(Post, { meta: this.meta, ...post })) }
      </div>
    `
  }
}

const parsePost = post => {
  let [meta, ...text] = post.split(',')
  let [user, id, time, re] = meta.split('#')
  return { id, user, time, re, text }
}

const toTree = wall => {
  const tree = []
  const parsed = [...wall].map(parsePost).sort((a, b) => a.time - b.time)
  const map = parsed.reduce((p, n) => (p[n.id] = n, p), {})
  parsed.forEach(post => {
    if (post.re)
      (map[post.re].replies = map[post.re].replies || []).push(post)
    else
      tree.push(post)
  })
  return tree
}

const parseMeta = _meta => {
  let [meta, ...values] = _meta.split(',')
  let [user, time, type] = meta.split('#')
  return { user, time, type, values }
}

const toMeta = meta => {
  const parsed = [...meta].map(parseMeta).sort((a, b) => a.time - b.time)
  const data = {
    getUser (cid) {
      return cid in this.nicks ? this.nicks[cid] : cid
    },
    nicks: {}
  }
  parsed.forEach(({ type, user, values }) => {
    switch (type) {
      case 'nick':
        data.nicks[user] = values[0]
        break
    }
  })
  return data
}

const state = {
  newPost: '',

  get wall () {
    return toTree(chat)
  },

  get meta () {
    return toMeta(meta)
  },

  get textareaRows () {
    return this.newPost.split('\n').length
  }
}

state.newPost = ''

function getUser (cid) {
  return peers.filter(peer => peer.cid === cid)[0]?.username || cid
}

function htmlescape (text, initialSpace) {
  text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;')
  if (initialSpace) text = text.replace(/ /,'&nbsp;')
  return text
}

function onrender (el) {
  if (el instanceof Element) {
    const expr = el.getAttribute('onrender')
    if (expr) {
      const fn = new Function(expr)
      fn.call(el)
    }
  }
}

const el = container
const app = window.app = $(App, state, { offerToPeer })

let scrollInitial = false
let scrollEl = el.querySelector('.main')
const render = window.render = () => {
  console.log('render')
  const html = app.toString(true)
  const isBottom = scrollEl && Math.round(scrollEl.scrollTop + scrollEl.clientHeight) >= scrollEl.scrollHeight - 50
  morphdom(el, html, {
    onNodeAdded: onrender,
    onElUpdated: onrender
  })
  if (!scrollInitial || isBottom) {
    scrollInitial = true
    scrollEl = el.querySelector('.main')
    scrollEl.scrollTop = scrollEl.scrollHeight
  }
}

document.addEventListener('render', render)

render()
