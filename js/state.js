const randomNick = () => {
  const nicks = [
    'john', 'anna', 'bob', 'suzanne', 'joe', 'mary', 'phil', 'julia', 'george', 'kate', 'chris', 'christine'
  ]
  return nicks[Math.random() * nicks.length | 0]
}

import { parse, lines } from './parse.js'

export default class State {
  constructor (app, data = '') {
    this.app = app
    this.data = new Set(data ? [data] : [
      app.net.format('iam', randomNick()),
      app.net.format('join', '#garden'),
      app.net.format('msg:#garden', 'hello')
    ])
    this.newPost = ''
  }

  get textareaRows () {
    return this.newPost.split('\n').length
  }

  get merged () {
    return new Set([this.app.net.peers.map(peer => lines(peer.data)), ...this.data].flat(Infinity))
  }

  get view () {
    return parse(this.merged)
  }
}
