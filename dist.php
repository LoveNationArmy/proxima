<?php

  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: *');
  header('Access-Control-Allow-Headers: *');

  $datadir = getcwd() . '/signals/';
  if (!is_dir($datadir)) {
    mkdir($datadir . 'offers', 0777, true);
    mkdir($datadir . 'answers', 0777, true);
  }

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $accept = explode(',', $_SERVER['HTTP_ACCEPT'])[0];
      if ($accept === 'text/html' || $accept === '*/*') {
        // TODO: generate asymetric encryption tokens based on email + pass + secret?
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf8">
    <title>proxima</title>
<style>
/* color variable declarations */

body {
  --main-light: #abe;
  --main: #38f;
  --back: #fff;
  --text: #000;
  --dark: #999;
  --light: #ccc;
  --very-light: #eee;
}

body.dark {
  --main-light: #abe;
  --main: #49f;
  --text: #dadada;
  --back: #2c2c2c;
  --dark: #777;
  --light: #4a4a4a;
  --very-light: #555;
}

/* scrollbars */

::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    border-radius: 10px;
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5);
}

/* reset global styling */

* {
  margin: 0;
  box-sizing: border-box;
  cursor: default;
}

html, body, #container {
  width: 100%;
  height: 100%;
  color: var(--text);
  background: var(--back);
  font-size: 10pt;
  font-family: -apple-system, BlinkMacSystemFont, "San Francisco", "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial, sans-serif;
}

button {
  background-image: linear-gradient(var(--back), var(--light));
  border: 1px solid var(--light);
  color: var(--text);
  padding: 2px 6px;
  height: 21px;
}

button:active {
  background-image: linear-gradient(var(--light), var(--back));
}

textarea {
  font: inherit;
  line-height: 11pt;
  color: var(--text);
  background: var(--back);
  border-color: var(--light);
}

textarea.pre {
  white-space: pre-line;
}

.active {
  color: var(--main);
  font-weight: bold;
}

.pre {
  white-space: pre;
  font-family: monospace;
  font-size: 12px;
}

.dim {
  opacity: 0.25;
}

/* app */

a {
  color: var(--main);
  cursor: pointer;
}

a:visited {
  color: var(--main-light);
}

.app {
  display: flex;
  width: 100%;
  height: 100%;
  min-height: 100%;
  max-height: 100%;
}

.app h1 a {
  color: var(--main);
}

/* main */

.main {
  display: flex;
  align-items: flex-end;
  width: 80%;
  overflow-y: scroll;
}

/* side */

.side {
  padding: 10px;
  width: 20%;
  background: var(--very-light);
  overflow-wrap: break-word;
  overflow-y: scroll;
}

/* chatarea */

.chatarea {
  width: 100%;
  max-height: 100vh;
  overflow-wrap: break-word;
}

/* wall */

.wall {
  padding: 10px;
  overflow-wrap: break-word;
}

/* streams */

.streams {
  position: fixed;
  display: flex;
  top: 5px;
  right: 5px;
  flex-flow: row nowrap;
  align-items: flex-start;
  justify-content: flex-end;
}

.streams video {
  margin: 5px;
  width: 100%;
}

.streams #localVideo {
  width: 100px;
}

.streams #remoteVideo {
  width: 300px;
  /*min-height: 100%;*/
}

/* chatbar */

.chatbar {
  display: flex;
  align-items: center;
  padding: 5px;
  border-top: 1px solid var(--light);
}

.chatbar textarea {
  flex: 1;
}

.chatbar > * {
  margin: 0 2px;
}

.chatbar .target {
  font-size: 12px;
  color: var(--dark);
}

/* user */

.user {
  display: inline-block;
  margin-top: 5px;
  margin-right: 5px;
  text-decoration: none;
}

/* post */

.post {
  display: inline-block;
  max-width: 100%;
}

.post info {
  opacity: 0.4;
  font-size: 9px;
  vertical-align: middle;
}

.post info * {
  color: var(--light);
}

.post:hover info {
  opacity: 1;
}

.post {
  /*margin: 10px 15px 0 0;*/
}

.post .post {
  /*margin: 10px 15px;*/
}
</style>
  </head>
  <body class="dark">
    <div id="container"></div>
    <script>window.base = document.location.origin</script>
    <script>localStorage.token = window.token = localStorage.token || '<?php echo bin2hex(random_bytes(16)) ?>'</script>
<script>
(function(){"use strict";let handlerIdIncrement=0;window.handlers={};class VElement{constructor(Class,ref={},closure={}){const self=this;this.instance=new Class(ref);this.ref=ref;this.proxy=new Proxy(this,{get(obj,prop){let value=prop in self.instance?self.instance[prop]:prop in closure?closure[prop]:prop in ref?ref[prop]:obj[prop];if(typeof value==="function"){const method=value;const handlerId=`${prop}${handlerIdIncrement++}`;const handler=el=>{const handlerProxy=new Proxy(el,{get(obj,prop){let value;if(prop==="el")return el;if(prop in el){value=el[prop];if(typeof value==="function"){return value.bind(el)}else{return value}}return self.proxy[prop]},set(obj,prop,value){if(prop in el){el[prop]=value}else{ref[prop]=value}return true}});return(...args)=>{let result=method.apply(handlerProxy,args);if(result===false)return false;if(result instanceof Promise){return result.then(()=>self.render()).then(()=>result)}else{self.render();return result}}};window.handlers[handlerId]=handler;handler.toString=()=>`handlers['${handlerId}'](this)`;value=function handlerfn(...args){return handler(this.el).apply(this,args)};value.toString=handler.toString}return value}})}render(){const renderEvent=new CustomEvent("render");document.dispatchEvent(renderEvent)}toString(top){if(top)window.handlers={};return this.instance.template.call(this.proxy).trim().replace(/(>)(\s+)/g,"$1$2")}}const $=function $(...args){return new VElement(...args)};$.map=(array,fn)=>array.map(fn).join("");$.class=object=>Object.keys(object).filter(key=>!!object[key]).join(" ");const{SHOW_DOCUMENT_FRAGMENT:SHOW_DOCUMENT_FRAGMENT,SHOW_ELEMENT:SHOW_ELEMENT,SHOW_TEXT:SHOW_TEXT}=NodeFilter;const whitespaceTagNames=["PRE","TEXTAREA"];const map=new WeakMap;const trim=html=>html.trim().replace(/(>)(\s+)|(\s+)(<\/)/g,"$1$4");function parse(html,context={}){const el=document.createElement("div");el.innerHTML=html;const fragment=document.createDocumentFragment();while(el.hasChildNodes())fragment.appendChild(el.firstChild);const tree=document.createTreeWalker(fragment,SHOW_DOCUMENT_FRAGMENT|SHOW_ELEMENT|SHOW_TEXT,null,false);const root={tag:"div",attrs:{},children:[],context:context};while(tree.nextNode()){const node=tree.currentNode;const parent=map.get(node.parentNode);let v;if(node.nodeType===Node.TEXT_NODE){v=context.trim&&!whitespaceTagNames.includes(node.parentNode.tagName)?trim(node.textContent):node.textContent}else{v={tag:node.tagName,...attrs(node),children:[],context:context};for(const name in v.events){const fn=new Function("event","context",`with (context) { ${v.events[name]} }`);v.events[name]=function(event){return fn.call(this,event,v.context)}}}map.set(node,v);if(parent)parent.children.push(v);else root.children.push(v)}return root}const attrs=node=>[...node.attributes].reduce((p,n)=>(p[["attrs","events"][+(n.name.indexOf("on")===0)]][n.name]=n.value,p),{attrs:{},events:{}});const update=(node,v,parent,updated=v.updated)=>{if(!v.tag){return node.data!==v&&(parent.updated=true)&&(node.data=v)}for(const name in v.events){node[name]=v.events[name],updated=true}for(const name in v.attrs){if(node.getAttribute(name)!==v.attrs[name]&&(updated=true)){node.setAttribute(name,v.attrs[name])}}for(const{name:name}of[...node.attributes]){if(!(name in v.attrs)&&(updated=true)){node.removeAttribute(name)}}return v.updated=updated};const create=v=>v.tag&&(v.rendered=true)?document.createElement(v.tag):document.createTextNode(v);function render(currentNode,v){const prev=currentNode.childNodes;const next=v.children;while(prev.length>next.length){currentNode.removeChild(currentNode.lastChild)}for(const[i,child]of next.entries()){let node=prev[i];if(!node){currentNode.appendChild(node=create(child))}else if(node.tagName!==child.tag){currentNode.replaceChild(node=create(child),prev[i])}update(node,child,v);if(child.children)render(node,child)}if(v.updated)dispatch(currentNode,"onupdate");if(v.rendered)dispatch(currentNode,"onrender")}const dispatch=(node,event,fn=node.tagName&&node[event])=>fn&&fn.call(node);function dom(parent,html,context){render(parent,parse(html,context))}function randomId(){return(Math.random()*1e7|0).toString(36)+(Math.random()*1e7|0).toString(36)}var seed=(new Date).getTime();async function generateKeyPair(){const keyPair=await crypto.subtle.generateKey({name:"RSA-OAEP",hash:"SHA-256",modulusLength:4096,publicExponent:new Uint8Array([1,0,1])},true,["encrypt","decrypt"]);return{privateKey:keyPair.privateKey,publicKey:await crypto.subtle.exportKey("jwk",keyPair.publicKey)}}async function encrypt(publicKey,text){publicKey=await crypto.subtle.importKey("jwk",publicKey,{name:"RSA-OAEP",hash:"SHA-256"},true,["encrypt"]);const encryptKey=await window.crypto.subtle.generateKey({name:"AES-GCM",length:256},true,["encrypt","decrypt"]);const iv=window.crypto.getRandomValues(new Uint8Array(12));const textEncrypted=await window.crypto.subtle.encrypt({name:"AES-GCM",iv:iv},encryptKey,(new TextEncoder).encode(text));const exportKey=await crypto.subtle.exportKey("jwk",encryptKey);exportKey.iv=base64.fromArrayBuffer(iv);const keyEncrypted=await window.crypto.subtle.encrypt({name:"RSA-OAEP"},publicKey,(new TextEncoder).encode(JSON.stringify(exportKey)));return{text:base64.fromArrayBuffer(new Uint8Array(textEncrypted)),key:base64.fromArrayBuffer(new Uint8Array(keyEncrypted))}}async function decrypt(privateKey,encrypted){const textBuffer=await base64.toArrayBuffer(encrypted.text);const keyBuffer=await base64.toArrayBuffer(encrypted.key);const key=JSON.parse((new TextDecoder).decode(await window.crypto.subtle.decrypt({name:"RSA-OAEP"},privateKey,keyBuffer)));const decryptKey=await crypto.subtle.importKey("jwk",key,{name:"AES-GCM"},true,["encrypt","decrypt"]);const text=(new TextDecoder).decode(await window.crypto.subtle.decrypt({name:"AES-GCM",iv:new Uint8Array(await base64.toArrayBuffer(key.iv))},decryptKey,textBuffer));return text}const base64={toArrayBuffer:s=>fetch(`data:application/octet-binary;base64,${s}`).then(res=>res.arrayBuffer()),fromArrayBuffer:b=>btoa(String.fromCharCode(...b))};function formatter(cid){return(...message)=>[`${performance.timeOrigin+performance.now()}.${randomId()}`,cid,message.join(" ")].join("\t")}async function parse$1(data,filter){const nicks=new Map([["notice","*** Notice"]]);const keys=new Map;const channels=new Map;const offers=new Map;const answers=new Map;const dataParsed=new Set;const channel=name=>channels.set(name,channels.get(name)||{users:new Set,wall:[],video:false,audio:false}).get(name);const parsed=lines(data).map(parseLine).sort((a,b)=>a.id<b.id?-1:a.id>b.id?1:0);const map=parsed.reduce((p,n)=>(p[n.id]=n,p),{});const view={nicks:nicks,keys:keys,channel:channel,channels:channels,offers:offers,answers:answers,parsed:parsed};for(const msg of parsed){if(filter){const result=await filter({...msg,...view});if(result===false)continue}switch(msg.command){case"re":(map[msg.target].replies=map[msg.target].replies||[]).push(msg);break;case"iam":nicks.set(msg.cid,msg.text);break;case"key":keys.set(msg.cid,msg.text);break;case"join":channel(msg.text).users.add(msg.cid);break;case"part":channel(msg.text).users.delete(msg.cid);break;case"offer":break;case"answer":break;case"trackoffer":break;case"trackanswer":break;case"connect":break;case"disconnect":break;case"syncme":break;case"notice":channel(msg.target).wall.push(msg);break;case"msg":channel(msg.target).wall.push(msg);break;default:console.error("Malformed message:",msg);continue}dataParsed.add(format(msg))}return{...view,data:dataParsed}}function format(msg){return`${[msg.id,msg.cid,[[msg.command,msg.target].filter(Boolean).join(":"),msg.text].filter(Boolean).join(" ")].join("\t")}`}function lines(data){return[...data].map(chunk=>chunk.split("\r\n")).flat(Infinity)}function diff(target,source){const set=new Set;for(const value of new Set(lines(source)).values()){if(!target.has(value))set.add(value)}return set}const parseLine=line=>{let[id,cid,...rest]=line.split("\t");rest=rest.join("\t").split(" ");let[command,target]=rest[0].split(":");let text=rest.slice(1).join(" ");return{id:id,cid:cid,command:command,target:target,text:text}};function emit(target,name,data){return target.dispatchEvent(new CustomEvent(name,{detail:data}))}function once(emitter,name){return new Promise(resolve=>emitter.addEventListener(name,resolve,{once:true}))}function on(emitter,name,until){let resolve=()=>{},reject=()=>{};let needle=0;const listener=event=>{listener.events.push(event);resolve(event)};listener.events=[];listener.end=event=>{emitter.removeEventListener(name,listener);listener.ended=true;reject(event)};listener[Symbol.asyncIterator]=async function*(){while(needle<listener.events.length){yield listener.events[needle++]}while(!listener.ended){try{yield new Promise((...callbacks)=>[resolve,reject]=callbacks)}catch(_){break}}};emitter.addEventListener(name,listener);if(until)once(emitter,until).then(listener.end);return listener}class Handlers{constructor(app){this.app=app}handle(data){if(this[data.command])return this[data.command](data)}async join({cid:cid,peer:peer}){if(peer.cid===cid){const snapshot=this.app.state.merge(false,true);peer.send(snapshot,await parse$1(snapshot))}}async offer({target:target,cid:cid,text:text}){if(target!==this.app.net.cid)return;const decryptedOffer=await decrypt(this.app.keys.privateKey,JSON.parse(text));this.app.net.answerTo({cid:cid,type:"offer",sdp:JSON.parse(decryptedOffer)});return false}async answer({target:target,cid:cid,text:text}){if(target!==this.app.net.cid)return;const offerPeer=this.app.net.offerPeers.get(cid);if(offerPeer){const decryptedAnswer=await decrypt(this.app.keys.privateKey,JSON.parse(text));await offerPeer.receiveAnswer({cid:cid,type:"answer",sdp:JSON.parse(decryptedAnswer)});this.app.net.offerPeers.delete(cid)}else{console.error("No such offer peer (double answer attempt?):",answer)}return false}async trackoffer({peer:peer,target:target,cid:cid,text:text}){if(target!==this.app.net.cid)return;if(peer.cid!==cid)return;await peer.connection.setRemoteDescription(JSON.parse(text));peer.localStream=await navigator.mediaDevices.getUserMedia({video:this.app.videoSettings});const videoTracks=peer.localStream.getVideoTracks();peer.connection.addTrack(videoTracks[0],peer.localStream);const answer=await peer.connection.createAnswer();answer.sdp=answer.sdp.replace(/a=ice-options:trickle\s\n/g,"");await peer.connection.setLocalDescription(answer);await once(peer.connection,"icecandidate");this.app.dispatch(`trackanswer:${cid}`,JSON.stringify(peer.connection.localDescription))}async trackanswer({peer:peer,target:target,cid:cid,text:text}){if(target!==this.app.net.cid)return;if(peer.cid!==cid)return;await peer.connection.setRemoteDescription(JSON.parse(text))}async syncme({peer:peer}){const snapshot=this.app.state.merge(false,true);peer.send(snapshot,await parse$1(snapshot));return false}}const randomNick=()=>{const nicks=["john","anna","bob","suzanne","joe","mary","phil","julia","george","kate","chris","christine"];return nicks[Math.random()*nicks.length|0]};class State{constructor(app,data=""){this.app=app;this.notices=new Set;this.data=new Set(data?[data]:[app.net.format("iam",randomNick()),app.net.format("key",JSON.stringify(app.keys.publicKey)),app.net.format("join","#garden"),app.net.format("msg:#garden","hello")]);this.newPost="";this.textareaRows=1;this.channelView="#garden"}merge(withNotices,withOut){let data=[this.app.net.peers.map(peer=>lines(peer.data.in)),...this.data];if(withNotices)data=[data,...this.notices];if(withOut)data=[data,...this.app.net.peers.map(peer=>lines(peer.data.out))];return new Set(data.flat(Infinity))}}function secs(n=Math.random()*6){return new Promise(resolve=>setTimeout(resolve,n*1e3))}function copy(obj){return JSON.parse(JSON.stringify(obj))}const OPTIONS={iceServers:[]};class Peer extends EventTarget{constructor(app,opts=OPTIONS){super();this.app=app;this.cid=null;this.data={in:new Set,out:new Set};this.closed=false;this.connected=false;this.connection=new RTCPeerConnection(opts);this.connection.onconnectionstatechange=()=>{switch(this.connection.connectionState){case"disconnected":case"failed":case"closed":this.close()}};this.connection.ontrack=e=>{this.remoteStream=e.streams[0]}}close(){if(this.closed)return;this.closed=true;this.connected=false;try{this.connection.close()}catch(_){}try{this.messages.end()}catch(_){}emit(this,"close")}send(data,view){let chunk=diff(new Set([...this.data.in,...this.data.out]),data);if(chunk.size){let set=new Set;for(const line of chunk.values()){const msg=parseLine(line);if(msg.command==="msg"&&msg.text[0]==="#"&&!view.channel(msg.target).users.has(this.cid)){continue}else if((msg.command==="join"||msg.command==="part")&&msg.text[0]!=="#"&&!msg.text.split(",").find(cid=>cid===this.cid)){continue}else if((msg.command==="trackoffer"||msg.command==="trackanswer")&&msg.target!==this.cid){continue}else{set.add(line)}}if(set.size){this.data.out=new Set([...this.data.out,...set]);try{this.channel.send([...set].join("\r\n"))}catch(error){console.error(error)}}}}async createOffer(){this.openChannel(this.connection.createDataChannel("data"));await once(this.connection,"negotiationneeded");const offer=await this.connection.createOffer();return this.createSignal(offer)}receiveAnswer(answer){this.cid=answer.cid;return this.connection.setRemoteDescription(answer)}async createAnswer(offer){this.cid=offer.cid;await this.connection.setRemoteDescription(offer);const answer=await this.connection.createAnswer();once(this.connection,"datachannel").then(({channel:channel})=>this.openChannel(channel));return this.createSignal({...offer,...copy(answer)})}async createSignal(signal){signal.sdp=signal.sdp.replace(/a=ice-options:trickle\s\n/g,"");await this.connection.setLocalDescription(signal);await Promise.race([once(this.connection,"icecandidate"),secs(30)]);return{...signal,...copy(this.connection.localDescription)}}async openChannel(channel){this.channel=channel;this.messages=on(this.channel,"message","close");await once(this.channel,"open");this.format=formatter(this.cid);this.connected=true;emit(this,"open");for await(const{data:data}of this.messages){const chunk=diff(new Set([...this.data.in,...this.data.out]),[data]);if(chunk.size)emit(this,"data",chunk)}this.close()}}let base=window.base||"http://localhost";const json=res=>res.json();const headers={Accept:"application/json",Authorization:`Bearer ${window.token}`};const get=(url,opts={})=>fetch(url,Object.assign(opts,{headers:headers})).then(json);const post=(url,data)=>{const body=new FormData;Object.keys(data).forEach(key=>body.append(key,data[key]));return get(url,{method:"POST",body:body})};const getNextOffer=not=>get(`${base}/?not=${not}`);const sendOffer=sdp=>post(base,{type:sdp.type,sdp:sdp.sdp});const sendAnswer=sdp=>post(base,{id:sdp.id,type:sdp.type,sdp:sdp.sdp});const getAnswer=offer=>get(`${base}/?id=${offer.id}`);const pollForAnswer=async(offer,retries=10)=>{for(let i=0,answer;i<retries;i++){try{answer=await getAnswer(offer);return answer}catch(error){await secs()}}throw new Error("Waiting for answer failed: Max retries reached")};const OPTIONS$1={maxPeers:6};class Net extends EventTarget{constructor(app,opts=OPTIONS$1){super();this.cid=window.token.slice(-5);this.app=app;this.opts=opts;this.peers=[];this.offerPeers=new Map;this.format=formatter(this.cid)}async connect(){this.make("offer");await secs();this.make("answer")}async addPeer(peer){let snapshot;if(this.peers.find(p=>p.cid===peer.cid)){console.warn(`Already connected with "${peer.cid}", dropping connection...`);peer.close();return}this.peers.push(peer);this.app.dispatch("connect",peer.cid);this.app.dispatch("join",[this.cid,peer.cid].sort().join());snapshot=this.app.state.merge(false,true);peer.send(snapshot,await parse$1(snapshot));emit(this,"peer",peer);emit(this,`peer:${peer.cid}`,peer);for await(const{detail:data}of on(peer,"data","close")){snapshot=this.app.state.merge(false,true);const chunk=diff(snapshot,data);peer.data.in=new Set([...peer.data.in,...data]);if(chunk.size){parse$1(chunk,msg=>this.app.handlers.handle({...msg,peer:peer})).then(async view=>{if(view.data.size){this.broadcast(view.data,peer,await parse$1(new Set([...view.data,...snapshot])))}emit(this,"data",{data:data,view:view,peer:peer})})}}this.peers.splice(this.peers.indexOf(peer),1);this.app.dispatch("part",[this.cid,peer.cid].sort().join());this.app.dispatch("disconnect",peer.cid);emit(this,"peer",peer)}async broadcast(data,peer={},view){this.peers.filter(p=>p.cid!==peer.cid).forEach(peer=>peer.send(data,view))}async lessThanMaxPeers(){while(this.peers.length>=this.opts.maxPeers){await once(this,"peer")}return true}async make(type){while(true){await this.lessThanMaxPeers();await this.createPeer(type);await secs(2+this.peers.length**3)}}async offerTo(cid){this.app.state.channelView=[this.cid,cid].sort().join();if(this.peers.map(peer=>peer.cid).includes(cid)){console.warn(`Connection to ${cid} aborted, already connected.`);return}const peer=new Peer(this.app);const offer=await peer.createOffer();const encryptedOffer=await encrypt(JSON.parse(this.app.state.view.keys.get(cid)),JSON.stringify(offer.sdp));this.offerPeers.set(cid,peer);this.app.dispatch(`offer:${cid}`,JSON.stringify(encryptedOffer));await Promise.race([once(peer,"open"),secs(30)]);try{if(!peer.connected)throw new Error(`Connection timeout [by offer to ${cid}].`);this.addPeer(peer);return}catch(error){console.error(error)}peer.close()}async answerTo(offer){const peer=new Peer(this.app);try{const answer=await peer.createAnswer(offer);const encryptedAnswer=await encrypt(JSON.parse(this.app.state.view.keys.get(offer.cid)),JSON.stringify(answer.sdp));this.app.dispatch(`answer:${offer.cid}`,JSON.stringify(encryptedAnswer));await Promise.race([once(peer,"open"),secs(30)]);if(!peer.connected)throw new Error(`Connection timeout [by answer to ${offer.cid}].`);this.addPeer(peer);return}catch(error){console.error(error)}peer.close()}async createPeer(type){const peer=new Peer(this.app);try{if(type==="offer"){const offer=await sendOffer(await peer.createOffer());const answer=await pollForAnswer(offer);await peer.receiveAnswer(answer)}else if(type==="answer"){const known=this.peers.map(peer=>peer.cid).join();const offer=await getNextOffer(known);const answer=await peer.createAnswer(offer);await sendAnswer(answer)}await Promise.race([once(peer,"open"),secs(30)]);if(!peer.connected)throw new Error(`Connection timeout [${type}].`);this.addPeer(peer);return}catch(error){console.warn(error)}peer.close()}}class App{constructor(el){this.el=el;this.app=this;this.videoSettings={resizeMode:"crop-and-scale",facingMode:"user",frameRate:24,width:176,height:144}}async start(){this.net=new Net(this);this.keys=await generateKeyPair();this.state=new State(this,this.load());this.handlers=new Handlers(this);this.notice=formatter("notice");this.ui=$(UI,this);this.net.addEventListener("peer",()=>this.render());this.net.addEventListener("data",()=>this.render());document.addEventListener("render",()=>this.render());this.net.connect();this.render()}async dispatch(...message){message=this.net.format(...message);console.log("dispatch",message);this.state.data.add(message);this.net.broadcast([message],this.net,await parse$1(this.state.merge(false,true)))}load(){return localStorage.data||""}save(){localStorage.data=[...this.state.data].join("\r\n")}offerTo(cid){this.net.offerTo(cid)}async render(){this.state.view=await parse$1(this.state.merge(true));const html=this.ui.toString(true);dom(this.el,html,{trim:true})}}class UI{constructor(){this.isBottom=true}template(){const view=this.state.view;const channels=view.channels.keys();const channel=view.channels.get(this.state.channelView);const peers=this.app.net.peers.map(peer=>peer.cid);prevUser=null;return`\n      <div class="app">\n        <div class="side">\n          <div class="channels">\n            ${$.map([...channels].filter(c=>c[0]==="#"),c=>`\n              <div\n                class="channel ${$.class({active:c===this.state.channelView,dim:!view.channel(c).users.has(this.app.net.cid)})}"\n                onclick="${this.switchToChannel}('${c}')">${c[0]==="#"?c:c.split(",").map(cid=>view.nicks.get(cid)).join()}</div>\n            `)}\n          </div>\n          <div class="peers">\n            ${channel?$.map([...channel.users].filter(cid=>cid!==this.app.net.cid),cid=>`\n              <div\n                class="peer ${$.class({active:this.state.channelView.split(",").includes(cid),dim:!peers.includes(cid)})}"\n                onclick="${this.offerTo}('${cid}')">\n                ${view.nicks.get(cid)||cid}\n              </div>\n              `):""}\n          </div>\n        </div>\n        <div class="main" onscroll="${this.checkScrollBottom}()" onupdate="${this.scrollToBottom}()">\n          ${$(ChatArea,{view:view,target:this.state.channelView,app:this.app,state:this.state})}\n        </div>\n      </div>\n    `}switchToChannel(channel){this.state.channelView=channel}checkScrollBottom(){this.isBottom=Math.round(this.scrollTop+this.clientHeight)>=this.scrollHeight-50;return false}scrollToBottom(){if(this.isBottom)this.scrollTop=this.scrollHeight;return false}}class ChatArea{template(){const view=this.view;const channel=this.view.channels.get(this.target);const peerCid=this.target.split(",").find(cid=>cid!==this.app.net.cid);const peer=this.app.net.peers.find(peer=>peer.cid===peerCid);return channel?`\n      <div class="chatarea">\n        <div class="wall">\n          ${peer&&peer.localStream?`<div class="streams">`:""}\n          ${peer&&peer.localStream?`<video id="localVideo" onrender="${this.setStream}('local')" autoplay playsinline muted style="transform: scaleX(-1)"></video>`:""}\n          ${peer&&peer.remoteStream?`<video id="remoteVideo" onrender="${this.setStream}('remote')" autoplay playsinline muted></video>`:""}\n          ${peer&&peer.localStream?`</div>`:""}\n          ${$.map(channel.wall,post=>$(Post,post,{view:view,channel:channel}))}\n        </div>\n        <div class="chatbar">\n          <div class="target">${this.app.net.cid}</div>\n          <div class="nick">${view.nicks.get(this.app.net.cid)}</div>\n          <textarea\n            class="${$.class({pre:this.state.textareaRows>1})}"\n            onkeydown="${this.processKeyDown}(event)"\n            oninput="${this.processInput}()"\n            rows=${this.state.textareaRows}></textarea>\n          <button onclick="${this.createPost}()">send</button>\n          ${peer?`\n            <button onclick="${this.toggleVideo}()" class="${$.class({active:peer.localStream||peer.remoteStream})}">📹</button>\n            <button onclick="${this.toggleAudio}()">🎙️</button>\n          `:""}\n          <div class="target">${this.target[0]==="#"?this.target:view.nicks.get(this.target.split(",").find(cid=>cid!==this.app.net.cid))}</div>\n        </div>\n      </div>\n    `:""}setStream(type){const peerCid=this.target.split(",").find(cid=>cid!==this.app.net.cid);const peer=this.app.net.peers.find(peer=>peer.cid===peerCid);this.srcObject=null;this.srcObject=peer[type+"Stream"]}async toggleVideo(){const peerCid=this.target.split(",").find(cid=>cid!==this.app.net.cid);const peer=this.app.net.peers.find(peer=>peer.cid===peerCid);peer.localStream=await navigator.mediaDevices.getUserMedia({video:this.app.videoSettings});const videoTracks=peer.localStream.getVideoTracks();peer.connection.addTrack(videoTracks[0],peer.localStream);const offer=await peer.connection.createOffer();offer.sdp=offer.sdp.replace(/a=ice-options:trickle\s\n/g,"");await peer.connection.setLocalDescription(offer);await once(peer.connection,"icecandidate");this.app.dispatch(`trackoffer:${peerCid}`,JSON.stringify(peer.connection.localDescription))}toggleAudio(){}createPost(){if(!this.state.newPost.length)return;if(this.state.newPost[0]==="/"){const command=this.state.newPost.slice(1).split(" ")[0];const value=this.state.newPost.split(" ").slice(1).join(" ");this.app.dispatch(this.state.newPost.slice(1));if(command==="join"){this.state.channelView=value}}else{this.app.dispatch(`msg:${this.state.channelView}`,this.state.newPost)}this.state.newPost=this.value="";this.state.textareaRows=1}processKeyDown(event){if(event.which===13){if(event.ctrlKey===true){const pos=this.selectionStart;this.value=this.value.slice(0,pos)+"\n"+this.value.slice(pos);this.processInput();this.selectionStart=this.selectionEnd=pos+1}else{event.preventDefault();this.createPost();return false}}else{return false}}processInput(arg){const rows=this.state.textareaRows;this.state.newPost=this.value;const computed=window.getComputedStyle(this.el);const newRows=Math.max(this.state.newPost.split("\n").length,Math.floor(this.scrollHeight/parseFloat(computed.lineHeight)));if(newRows===rows)return false;this.state.textareaRows=newRows}}let prevUser,prevTime;class Post{template(){const lastPrevUser=prevUser;const lastPrevTime=lastPrevUser!==this.cid?0:prevTime;prevUser=this.cid;prevTime=parseInt(this.time);return`\n      <br>\n      <div class="post ${$.class({dim:!this.channel.users.has(this.cid)})}">\n        ${lastPrevUser!==this.cid?`<a class="user" href="/#~${this.cid}">${htmlescape(this.view.nicks.get(this.cid))}:</a>`:``}\n        ${prevTime-lastPrevTime>1e3*60?`\n        <info>\n          \x3c!-- <time>${new Date(+this.time).toLocaleString()}</time> --\x3e\n          <a href="#">reply</a>\n        </info>`:""}\n        <p\n          class="${$.class({pre:this.text.includes("\n")})}"\n          >${htmlescape(this.text,this.text.includes("\n"))}</p>\n        ${$.map(this.replies||[],post=>$(Post,{view:this.view,...post}))}\n      </div>\n    `}}function htmlescape(text="",initialSpace=false){text=text.replace(/&/g,"&amp;").replace(/</g,"&lt;");if(initialSpace)text=text.replace(/ /,"&nbsp;");return text}const app=window.app=new App(container);app.start()})();
</script>
  </body>
</html><?php
      } else if ($accept === 'application/json') {
        header('Content-Type: application/json');
        $token = explode(' ', apache_request_headers()['Authorization'])[1];
        if (!$token) {
          http_response_code(401); // Unauthorized
          exit(1);
        }

        if (isset($_GET['id'])) {
          $id = $_GET['id'];
          $filename = $datadir . 'answers/' . $id;
          $handle = fopen($filename, 'r+');
          flock($handle, LOCK_EX);
          $size = filesize($filename);
          if (!$handle || !$size) {
            http_response_code(404);
            exit(1);
          }
        } else {
          $cid = substr($token, -5);
          $not = explode(',', $_GET['not']);
          array_push($not, $cid);
          $offers = array_reverse(array_slice(scandir($datadir . 'offers'), 2));
          $offers = array_filter($offers, function ($v) {
            global $not;
            $offer_cid = array_pop(explode('.', $v));
            return !in_array($offer_cid, $not);
          });
          if (!$offers) {
            http_response_code(404);
            echo '{}';
            exit(1);
          }
          foreach ($offers as $id) {
            $filename = $datadir . 'offers/' . $id;
            $handle = fopen($filename, 'r+');
            if (!$handle) continue;
            if (!flock($handle, LOCK_EX)) continue;
            $size = filesize($filename);
            if (!$size) continue;
            break;
          }
          if (!$handle) {
            http_response_code(404);
            echo '{}';
            exit(1);
          }
        }

        $contents = fread($handle, $size);
        rewind($handle);
        ftruncate($handle, 0);
        flock($handle, LOCK_UN);
        unlink($filename);
        echo $contents;
      } else {
        http_response_code(415); // Unsupported media type
      }
    break;

    case 'POST':
      header('Content-Type: application/json');
      $token = explode(' ', apache_request_headers()['Authorization'])[1];
      if (!$token) {
        http_response_code(401); // Unauthorized
        exit(1);
      }

      $cid = substr($token, -5);
      $signal = $_POST;
      if (isset($_POST['id'])) {
        $prefix = 'answers/';
        $id = $_POST['id'];
        $signal['cid'] = $cid;
      } else {
        $prefix = 'offers/';
        $id = microtime(true) . '.' . $cid;
        $signal['id'] = bin2hex(random_bytes(16));
        $signal['cid'] = $cid;
      }
      $json = json_encode($signal);
      $filename = $datadir . $prefix . $id;
      file_put_contents($filename, $json);
      echo $json;
    break;

    case 'OPTIONS':
      break;

    default:
      http_response_code(405); // Method not allowed
      exit(1);
  }
?>