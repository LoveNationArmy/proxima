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
    this.notices = new Set()
    this.data = new Set(data ? [data] : [
      app.net.format('iam', randomNick()),
      app.net.format('key', JSON.stringify(app.keys.publicKey)),
      app.net.format('join', '#garden'),
      app.net.format('msg:#garden', 'hello')
    ])
    this.newPost = ''
    this.textareaRows = 1
  }

  merge (withNotices, withOut) {
    let data = [
      this.app.net.peers.map(peer => lines(peer.data.in)),
      ...this.data
    ]

    if (withNotices) data = [data, ...this.notices]
    if (withOut) data = [data, ...this.app.net.peers.map(peer => lines(peer.data.out))]

    return new Set(data.flat(Infinity))
  }

  get view () {
    return parse(this.merge(true))
  }
}
