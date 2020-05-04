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
  const nicks = new Map([['notice', '*** Notice']])
  const keys = new Map()
  const channels = new Map()
  const offers = new Map()
  const answers = new Map()
  const channel = name => channels.set(name, channels.get(name) || { users: new Set(), wall: [] }).get(name)
  const parsed = lines(data).map(parseLine).sort((a, b) => a.id < b.id ? -1 : a.id > b.id ? 1 : 0)
  const map = parsed.reduce((p, n) => (p[n.id] = n, p), {})
  parsed.forEach(msg => {
    switch (msg.command) {
      case 're': (map[msg.param].replies = map[msg.param].replies || []).push(msg); break
      case 'iam': nicks.set(msg.cid, msg.text); break
      case 'key': keys.set(msg.cid, msg.text); break
      case 'join': channel(msg.text).users.add(msg.cid); break
      case 'part': channel(msg.text).users.delete(msg.cid); break
      case 'offer': offers.set(msg.param, { cid: msg.cid, sdp: msg.text }); break
      case 'answer': answers.set(msg.param, { cid: msg.cid, sdp: msg.text }); break
      case 'connect': break; //console.log('connect', msg.text); break
      case 'disconnect': break; //console.log('disconnect', msg.text); break
      case 'notice': channel(msg.param).wall.push(msg); break
      case 'msg': channel(msg.param).wall.push(msg); break
      default: console.error('Malformed message:', msg)
    }
  })
  return { nicks, keys, channels, offers, answers }
}

export function lines (data) {
  return [...data].map(chunk => chunk.split('\r\n')).flat(Infinity)
}

export function diff (target, source) {
  const set = new Set()

  for (const value of new Set(lines(source)).values()) {
    if (!target.has(value)) set.add(value)
  }

  return set
}

const parseLine = line => {
  let [id, cid, ...rest] = line.split('\t')
  rest = rest.join('\t').split(' ')
  let [command, param] = rest[0].split(':')
  let text = rest.slice(1).join(' ')
  return { id, cid, command, param, text }
}
