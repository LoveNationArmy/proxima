const base = 'http://localhost'
const headers = { Accept: 'application/json', Authorization: 'Bearer abc123' }

let id, id2

describe('send offer', () => {
  it('should respond with object item', async () => {
    const body = new FormData()
    body.append('data', 'foo')
    const response = await fetch(base, { method: 'POST', body, headers })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.data).to.equal('foo')
    id = json.id
  })

  it('should respond with object item', async () => {
    await new Promise(resolve => setTimeout(resolve, 50))
    const body = new FormData()
    body.append('data', 'foo')
    const response = await fetch(base, { method: 'POST', body, headers })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.data).to.equal('foo')
    id2 = json.id
  })
})

describe('get next offer', () => {
  it('should respond with not found with same bearer', async () => {
    const response = await fetch(base, { headers })
    expect(response.status).to.equal(404)
  })

  it('should respond with object item as a different user', async () => {
    headers.Authorization = 'Bearer xyz789'
    const response = await fetch(base, { headers })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.id).to.equal(id2)
    expect(json.data).to.equal('foo')
  })

  it('should respond with object item', async () => {
    const response = await fetch(base, { headers })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.id).to.equal(id)
    expect(json.data).to.equal('foo')
  })
})

describe('send answer', () => {
  it('should respond with object item', async () => {
    const body = new FormData()
    body.append('id', id)
    body.append('data', 'bar')
    const response = await fetch(base, { method: 'POST', body, headers })
    const json = await response.json()
    expect(json.data).to.equal('bar')
  })
})

describe('read answer', () => {
  it('should respond with object item', async () => {
    const response = await fetch(`${base}/?id=${id}`, { headers })
    const json = await response.json()
    expect(json.data).to.equal('bar')
  })
})
