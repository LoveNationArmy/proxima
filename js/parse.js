import randomId from './lib/random-id.js'

export function formatter (cid) {
  return (...message) => {
    return [
      `${performance.timeOrigin + performance.now()}.${randomId()}`,
      cid,
      message.join(' ')
    ].join('\t')
  }
}

export function parse (data) {
  const nicks = new Map()
  const channels = new Map()
  const channel = name => channels.set(name, channels.get(name) || { users: new Set(), wall: [] }).get(name)
  const parsed = lines(data).map(parseLine).sort((a, b) => a.id < b.id ? -1 : a.id > b.id ? 1 : 0)
  const map = parsed.reduce((p, n) => (p[n.id] = n, p), {})
  parsed.forEach(msg => {
    switch (msg.command) {
      case 're': (map[msg.param].replies = map[msg.param].replies || []).push(msg); break
      case 'iam': nicks.set(msg.cid, msg.text); break
      case 'join': channel(msg.text).users.add(msg.cid); break
      case 'part': channel(msg.text).users.delete(msg.cid); break
      case 'msg': channel(msg.param).wall.push(msg); break
      default: console.error('Malformed message:', msg)
    }
  })
  return { nicks, channels }
}

export function lines (data) {
  return [...data].map(chunk => chunk.split('\r\n')).flat(Infinity)
}

export function merge (target, source) {
  const chunk = new Set()

  lines(source).forEach(line => {
    if (!target.has(line)) {
      target.add(line)
      chunk.add(line)
    }
  })

  return chunk
}

const parseLine = line => {
  let [id, cid, ...rest] = line.split('\t')
  rest = rest.join('\t').split(' ')
  let [command, param] = rest[0].split(':')
  let text = rest.slice(1).join(' ')
  return { id, cid, command, param, text }
}
