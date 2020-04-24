const base = 'http://localhost'

let id, id2

describe('posting question data', () => {
  it('should respond with object item', async () => {  
    const body = new FormData()
    body.append('d', 'question')
    const response = await fetch(base, { method: 'POST', body })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.d).to.equal('question')
    id = json.id
  })

  it('should respond with object item', async () => {  
    const body = new FormData()
    body.append('d', 'question')
    const response = await fetch(base, { method: 'POST', body })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.d).to.equal('question')
    id2 = json.id
  })
})

describe('getting data', () => {
  it('should respond with object item', async () => {  
    const response = await fetch(`${base}/?id=offers/${id}`)
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.id).to.equal(id)
    expect(json.d).to.equal('question')
  })
})

describe('getting list of items', () => {
  it('should respond with list of item ids', async () => {  
    const response = await fetch(base)
    const json = await response.json()
    expect(json).to.be.an('array')
    expect(id).to.be.oneOf(json)
    expect(id2).to.be.oneOf(json)
  })
})

describe('posting answer data', () => {
  it('should respond with object item', async () => {  
    const body = new FormData()
    body.append('id', id)
    body.append('d', 'answer')
    const response = await fetch(base, { method: 'POST', body })
    const json = await response.json()
    expect(json.id).to.be.a('string')
    expect(json.id).to.equal(id)
    expect(json.d).to.equal('answer')
  })
})

describe('removing an offer/answer', () => {
  it('should remove offer and answer', async () => {
    const url = `${base}/?id=${id}`
    const response = await fetch(url, { method: 'DELETE' })
    const text = await response.text()
    expect(text).to.equal('OK')
  })

  it('should remove offer', async () => {
    const url = `${base}/?id=${id2}`
    const response = await fetch(url, { method: 'DELETE' })
    const text = await response.text()
    expect(text).to.equal('OK')
  })
})
