// import { $ } from './element.js'
// import morphdom from './vdiff.js'

const App = ({ wall }, { createPost }) => `
  <div class="app">
    <h1><a href="/">stencil</a></h1>
    <textarea id="newPost"></textarea>
    <button onclick="${ createPost }(newPost.value), newPost.value = ''">post</button>
    <div class="peers">
      ${ $.map(peers, peer => `<div class="peer">${peer.cid}</div>`) }
    </div>
    <div class="wall">
      ${ $.map(toTree(wall).reverse(), post => $(Post, post)) }
    </div>
  </div>
`

const Post = ({ user, time, text, replies = [] }) => `
  <div class="post">
    <a class="user" href="/#~${user}">${getUser(user)}:</a>
    <info>
      <time>${new Date(+time).toLocaleString()}</time>
      <a href="#">reply</a>
    </info>
    <p>${text}</p>
    ${ $.map(replies, post => $(Post, post)) }
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

const state = {}

state.newPost = ''

state.users = {
  '1': 'stagas',
  '2': 'sergi',
  '3': 'fernando',
  '4': 'lamprou',
}

state.wall = chat

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
  const id = randomId()
  const post = `${cid}#${id}#${Date.now()},${msg}`
  state.wall.add(post)
  localStorage.chat = [...chat].sort().join('\r\n')
  channels.forEach(c => c.send(`${cid}\t${post}`))
}

function getUser (cid) {
  return peers.filter(peer => peer.cid === cid)[0]?.username || cid
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
