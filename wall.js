// import { $ } from './element.js'
// import morphdom from './vdiff.js'

const App = ({ wall, meta }, { createPost }) => `
  <div class="app">
    <div class="main">
      <h1><a href="/">stencil</a></h1>
      <div class="nick">${ meta.nicks[cid] }</div>
      <textarea id="newPost"></textarea>
      <button onclick="${ createPost }(newPost.value), newPost.value = ''">post</button>
      <div class="wall">
        ${ $.map(wall.reverse(), post => $(Post, { meta, ...post })) }
      </div>
    </div>
    <div class="side">
      <div class="peers">
        ${ $.map(peers.map(peer => meta.getUser(peer.cid)).sort(), nick =>
          `<div class="peer">${htmlescape(nick)}</div>`) }
        ${ $.map(Object.keys(meta.nicks)
          .filter(pcid => !peers.map(peer => peer.cid).includes(pcid) && pcid !== cid)
          .map(pcid => meta.getUser(pcid))
          .sort(), nick =>
          `<div class="peer in-network">${htmlescape(nick)}</div>`) }
      </div>
    </div>
  </div>
`

const Post = ({ meta, user, time, text, replies = [] }) => `
  <div class="post">
    <a class="user" href="/#~${user}">${htmlescape(meta.getUser(user))}:</a>
    <info>
      <time>${new Date(+time).toLocaleString()}</time>
      <a href="#">reply</a>
    </info>
    <p>${htmlescape(text.join(','))}</p>
    ${ $.map(replies, post => $(Post, { meta, ...post })) }
  </div>
`

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
  get wall () {
    return toTree(chat)
  },

  get meta () {
    return toMeta(meta)
  }
}

state.newPost = ''

state.users = {
  '1': 'stagas',
  '2': 'sergi',
  '3': 'fernando',
  '4': 'lamprou',
}

const wallToCome = [
  '1#1#1568283231023,Hello, world! This is the first post',
  '1#2#1568383235023,This is another post',
  '2#3#1568483232023,And another one!',
  '3#4#1568583233023,This one contains a link to <a href="http://foo.bar">http://foo.bar</a>',
  '3#5#1568883236023,and <a href="http://127.0.0.1:8080/">visited one</a>',
  '1#6#1568883436023#4,the link isn\'t working :(',
  '3#7#1568883533023#6,oh my bad',
  '2#8#1568883632023#4,its working for me',
  '3#9#1568883736023#4,then what the hell.. o.O',
  '4#10#1568883836023#4,what do you guys think we start using nfc',
]

function createPost (msg) {
  if (!msg.length) return
  const id = randomId()
  const post = `${cid}#${id}#${Date.now()},${msg}`
  chat.add(post)
  localStorage.chat = [...chat].sort().join('\r\n')
  channels.forEach(c => c.send(`${cid}\t${post}`))
}

function getUser (cid) {
  return peers.filter(peer => peer.cid === cid)[0]?.username || cid
}

function htmlescape (text) {
  return text.replace(/&/g,'&amp;').replace(/</g,'&lt;')
}

const el = container
const app = window.app = $(App, state, { createPost })

const render = window.render = () => {
  const html = app.toString()
  morphdom(el, html)
}

document.addEventListener('render', render)

setInterval(render, 300)

// const interval = setInterval(() => {
//   if (!wallToCome.length) return clearInterval(interval)
//   app.methods.createPost(wallToCome.shift())
// }, 2000)
