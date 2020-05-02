export default class State extends EventTarget {
  constructor (data = '') {
    super()
    this.token = window.token
    this.data = data.split('\r\n')
    this.newPost = ''
  }

  get cid () {
    return this.token.slice(-5)
  }

  get textareaRows () {
    return this.newPost.split('\n').length
  }

  get wall () {
    const tree = []
    const parsed = this.data.map(parse).sort((a, b) => a.time - b.time)
    const map = parsed.reduce((p, n) => (p[n.id] = n, p), {})
    parsed.forEach(msg => {
      if (msg.command === 're')
        (map[msg.param].replies = map[msg.param].replies || []).push(msg)
      else
        tree.push(msg)
    })
    return tree
  }
}

const parse = line => {
  const [time, cid, id, ...rest] = line.split('\t')
  const [command, param, ...text] = rest.join('\t').split(' ')
  return { time, cid, id, command, param, text: text.join(' ') }
}
