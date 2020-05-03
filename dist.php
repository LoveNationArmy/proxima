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

.pre {
  white-space: pre;
  font-family: monospace;
  font-size: 12px;
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

.side .peer:not(.direct) {
  color: var(--dark);
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
(function () {
  'use strict';

  // copyright 2019-2020 stagas
  // all rights reserved

  let handlerIdIncrement = 0;

  window.handlers = {};

  class VElement {
    constructor (Class, ref = {}, closure = {}) {
      const self = this;

      this.instance = new Class(ref);
      this.ref = ref;

      this.proxy = new Proxy(this, {
        get (obj, prop) {
          let value =
            prop in self.instance ? self.instance[prop] :
            prop in closure ? closure[prop] :
            prop in ref ? ref[prop] :
            obj[prop];

          if (typeof value === 'function') {
            const method = value;
            const handlerId = `${ prop }${ handlerIdIncrement++ }`;
            const handler = (el) => {
              // NOTE: we could also use self.proxy and a
              // currentElement=el variable to save proxy instances
              // but it will fail in async operations
              const handlerProxy = new Proxy(el, {
                get (obj, prop) {
                  let value;
                  if (prop === 'el') return el
                  if (prop in el) {
                    value = el[prop];
                    if (typeof value === 'function') {
                      return value.bind(el)
                    } else {
                      return value
                    }
                  }
                  return self.proxy[prop]
                },
                set (obj, prop, value) {
                  if (prop in el) {
                    el[prop] = value;
                  } else {
                    ref[prop] = value;
                  }
                  return true
                }
              });

              return (...args) => {
                let result = method.apply(handlerProxy, args);
                if (result === false) return false
                if (result instanceof Promise) {
                  return result.then(() => self.render()).then(() => result)
                } else {
                  self.render();
                  return result
                }
              }
            };

            window.handlers[handlerId] = handler;
            handler.toString = () => `handlers['${ handlerId }'](this)`;
            value = function handlerfn (...args) { return handler(this.el).apply(this, args) };
            value.toString = handler.toString;
          }

          return value
        }
      });
    }

    render () {
      const renderEvent = new CustomEvent('render');
      document.dispatchEvent(renderEvent);
    }

    toString (top) {
      if (top) window.handlers = {};
      return this.instance.template.call(this.proxy).trim().replace(/(>)(\s+)/g, '$1$2')
      // return this.instance.template.call(this.proxy).trim().replace(/(>)(\s+)|(\s+)(<\/)/g, '$1$4')
    }
  }

  const $ = function $ (...args) {
    return new VElement(...args)
  };

  $.map = (array, fn) => array.map(fn).join('');
  $.class = (object) => Object.keys(object).filter(key => !!object[key]).join(' ');

  var DOCUMENT_FRAGMENT_NODE = 11;

  function morphAttrs(fromNode, toNode) {
      var toNodeAttrs = toNode.attributes;
      var attr;
      var attrName;
      var attrNamespaceURI;
      var attrValue;
      var fromValue;

      // document-fragments dont have attributes so lets not do anything
      if (toNode.nodeType === DOCUMENT_FRAGMENT_NODE || fromNode.nodeType === DOCUMENT_FRAGMENT_NODE) {
        return;
      }

      // update attributes on original DOM element
      for (var i = toNodeAttrs.length - 1; i >= 0; i--) {
          attr = toNodeAttrs[i];
          attrName = attr.name;
          attrNamespaceURI = attr.namespaceURI;
          attrValue = attr.value;

          if (attrNamespaceURI) {
              attrName = attr.localName || attrName;
              fromValue = fromNode.getAttributeNS(attrNamespaceURI, attrName);

              if (fromValue !== attrValue) {
                  if (attr.prefix === 'xmlns'){
                      attrName = attr.name; // It's not allowed to set an attribute with the XMLNS namespace without specifying the `xmlns` prefix
                  }
                  fromNode.setAttributeNS(attrNamespaceURI, attrName, attrValue);
              }
          } else {
              fromValue = fromNode.getAttribute(attrName);

              if (fromValue !== attrValue) {
                  fromNode.setAttribute(attrName, attrValue);
              }
          }
      }

      // Remove any extra attributes found on the original DOM element that
      // weren't found on the target element.
      var fromNodeAttrs = fromNode.attributes;

      for (var d = fromNodeAttrs.length - 1; d >= 0; d--) {
          attr = fromNodeAttrs[d];
          attrName = attr.name;
          attrNamespaceURI = attr.namespaceURI;

          if (attrNamespaceURI) {
              attrName = attr.localName || attrName;

              if (!toNode.hasAttributeNS(attrNamespaceURI, attrName)) {
                  fromNode.removeAttributeNS(attrNamespaceURI, attrName);
              }
          } else {
              if (!toNode.hasAttribute(attrName)) {
                  fromNode.removeAttribute(attrName);
              }
          }
      }
  }

  var range; // Create a range object for efficently rendering strings to elements.
  var NS_XHTML = 'http://www.w3.org/1999/xhtml';

  var doc = typeof document === 'undefined' ? undefined : document;
  var HAS_TEMPLATE_SUPPORT = !!doc && 'content' in doc.createElement('template');
  var HAS_RANGE_SUPPORT = !!doc && doc.createRange && 'createContextualFragment' in doc.createRange();

  function createFragmentFromTemplate(str) {
      var template = doc.createElement('template');
      template.innerHTML = str;
      return template.content.childNodes[0];
  }

  function createFragmentFromRange(str) {
      if (!range) {
          range = doc.createRange();
          range.selectNode(doc.body);
      }

      var fragment = range.createContextualFragment(str);
      return fragment.childNodes[0];
  }

  function createFragmentFromWrap(str) {
      var fragment = doc.createElement('body');
      fragment.innerHTML = str;
      return fragment.childNodes[0];
  }

  /**
   * This is about the same
   * var html = new DOMParser().parseFromString(str, 'text/html');
   * return html.body.firstChild;
   *
   * @method toElement
   * @param {String} str
   */
  function toElement(str) {
      str = str.trim();
      if (HAS_TEMPLATE_SUPPORT) {
        // avoid restrictions on content for things like `<tr><th>Hi</th></tr>` which
        // createContextualFragment doesn't support
        // <template> support not available in IE
        return createFragmentFromTemplate(str);
      } else if (HAS_RANGE_SUPPORT) {
        return createFragmentFromRange(str);
      }

      return createFragmentFromWrap(str);
  }

  /**
   * Returns true if two node's names are the same.
   *
   * NOTE: We don't bother checking `namespaceURI` because you will never find two HTML elements with the same
   *       nodeName and different namespace URIs.
   *
   * @param {Element} a
   * @param {Element} b The target element
   * @return {boolean}
   */
  function compareNodeNames(fromEl, toEl) {
      var fromNodeName = fromEl.nodeName;
      var toNodeName = toEl.nodeName;

      if (fromNodeName === toNodeName) {
          return true;
      }

      if (toEl.actualize &&
          fromNodeName.charCodeAt(0) < 91 && /* from tag name is upper case */
          toNodeName.charCodeAt(0) > 90 /* target tag name is lower case */) {
          // If the target element is a virtual DOM node then we may need to normalize the tag name
          // before comparing. Normal HTML elements that are in the "http://www.w3.org/1999/xhtml"
          // are converted to upper case
          return fromNodeName === toNodeName.toUpperCase();
      } else {
          return false;
      }
  }

  /**
   * Create an element, optionally with a known namespace URI.
   *
   * @param {string} name the element name, e.g. 'div' or 'svg'
   * @param {string} [namespaceURI] the element's namespace URI, i.e. the value of
   * its `xmlns` attribute or its inferred namespace.
   *
   * @return {Element}
   */
  function createElementNS(name, namespaceURI) {
      return !namespaceURI || namespaceURI === NS_XHTML ?
          doc.createElement(name) :
          doc.createElementNS(namespaceURI, name);
  }

  /**
   * Copies the children of one DOM element to another DOM element
   */
  function moveChildren(fromEl, toEl) {
      var curChild = fromEl.firstChild;
      while (curChild) {
          var nextChild = curChild.nextSibling;
          toEl.appendChild(curChild);
          curChild = nextChild;
      }
      return toEl;
  }

  function syncBooleanAttrProp(fromEl, toEl, name) {
      if (fromEl[name] !== toEl[name]) {
          fromEl[name] = toEl[name];
          if (fromEl[name]) {
              fromEl.setAttribute(name, '');
          } else {
              fromEl.removeAttribute(name);
          }
      }
  }

  var specialElHandlers = {
      OPTION: function(fromEl, toEl) {
          var parentNode = fromEl.parentNode;
          if (parentNode) {
              var parentName = parentNode.nodeName.toUpperCase();
              if (parentName === 'OPTGROUP') {
                  parentNode = parentNode.parentNode;
                  parentName = parentNode && parentNode.nodeName.toUpperCase();
              }
              if (parentName === 'SELECT' && !parentNode.hasAttribute('multiple')) {
                  if (fromEl.hasAttribute('selected') && !toEl.selected) {
                      // Workaround for MS Edge bug where the 'selected' attribute can only be
                      // removed if set to a non-empty value:
                      // https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/12087679/
                      fromEl.setAttribute('selected', 'selected');
                      fromEl.removeAttribute('selected');
                  }
                  // We have to reset select element's selectedIndex to -1, otherwise setting
                  // fromEl.selected using the syncBooleanAttrProp below has no effect.
                  // The correct selectedIndex will be set in the SELECT special handler below.
                  parentNode.selectedIndex = -1;
              }
          }
          syncBooleanAttrProp(fromEl, toEl, 'selected');
      },
      /**
       * The "value" attribute is special for the <input> element since it sets
       * the initial value. Changing the "value" attribute without changing the
       * "value" property will have no effect since it is only used to the set the
       * initial value.  Similar for the "checked" attribute, and "disabled".
       */
      INPUT: function(fromEl, toEl) {
          syncBooleanAttrProp(fromEl, toEl, 'checked');
          syncBooleanAttrProp(fromEl, toEl, 'disabled');

          if (fromEl.value !== toEl.value) {
              fromEl.value = toEl.value;
          }

          if (!toEl.hasAttribute('value')) {
              fromEl.removeAttribute('value');
          }
      },

      TEXTAREA: function(fromEl, toEl) {
          var newValue = toEl.value;
          if (fromEl.value !== newValue) {
              fromEl.value = newValue;
          }

          var firstChild = fromEl.firstChild;
          if (firstChild) {
              // Needed for IE. Apparently IE sets the placeholder as the
              // node value and vise versa. This ignores an empty update.
              var oldValue = firstChild.nodeValue;

              if (oldValue == newValue || (!newValue && oldValue == fromEl.placeholder)) {
                  return;
              }

              firstChild.nodeValue = newValue;
          }
      },
      SELECT: function(fromEl, toEl) {
          if (!toEl.hasAttribute('multiple')) {
              var selectedIndex = -1;
              var i = 0;
              // We have to loop through children of fromEl, not toEl since nodes can be moved
              // from toEl to fromEl directly when morphing.
              // At the time this special handler is invoked, all children have already been morphed
              // and appended to / removed from fromEl, so using fromEl here is safe and correct.
              var curChild = fromEl.firstChild;
              var optgroup;
              var nodeName;
              while(curChild) {
                  nodeName = curChild.nodeName && curChild.nodeName.toUpperCase();
                  if (nodeName === 'OPTGROUP') {
                      optgroup = curChild;
                      curChild = optgroup.firstChild;
                  } else {
                      if (nodeName === 'OPTION') {
                          if (curChild.hasAttribute('selected')) {
                              selectedIndex = i;
                              break;
                          }
                          i++;
                      }
                      curChild = curChild.nextSibling;
                      if (!curChild && optgroup) {
                          curChild = optgroup.nextSibling;
                          optgroup = null;
                      }
                  }
              }

              fromEl.selectedIndex = selectedIndex;
          }
      }
  };

  var ELEMENT_NODE = 1;
  var DOCUMENT_FRAGMENT_NODE$1 = 11;
  var TEXT_NODE = 3;
  var COMMENT_NODE = 8;

  function noop() {}

  function defaultGetNodeKey(node) {
    if (node) {
        return (node.getAttribute && node.getAttribute('id')) || node.id;
    }
  }

  function morphdomFactory(morphAttrs) {

      return function morphdom(fromNode, toNode, options) {
          if (!options) {
              options = {};
          }

          if (typeof toNode === 'string') {
              if (fromNode.nodeName === '#document' || fromNode.nodeName === 'HTML' || fromNode.nodeName === 'BODY') {
                  var toNodeHtml = toNode;
                  toNode = doc.createElement('html');
                  toNode.innerHTML = toNodeHtml;
              } else {
                  toNode = toElement(toNode);
              }
          }

          var getNodeKey = options.getNodeKey || defaultGetNodeKey;
          var onBeforeNodeAdded = options.onBeforeNodeAdded || noop;
          var onNodeAdded = options.onNodeAdded || noop;
          var onBeforeElUpdated = options.onBeforeElUpdated || noop;
          var onElUpdated = options.onElUpdated || noop;
          var onAfterElUpdated = options.onAfterElUpdated || noop;
          var onBeforeNodeDiscarded = options.onBeforeNodeDiscarded || noop;
          var onNodeDiscarded = options.onNodeDiscarded || noop;
          var onBeforeElChildrenUpdated = options.onBeforeElChildrenUpdated || noop;
          var childrenOnly = options.childrenOnly === true;

          // This object is used as a lookup to quickly find all keyed elements in the original DOM tree.
          var fromNodesLookup = Object.create(null);
          var keyedRemovalList = [];

          function addKeyedRemoval(key) {
              keyedRemovalList.push(key);
          }

          function walkDiscardedChildNodes(node, skipKeyedNodes) {
              if (node.nodeType === ELEMENT_NODE) {
                  var curChild = node.firstChild;
                  while (curChild) {

                      var key = undefined;

                      if (skipKeyedNodes && (key = getNodeKey(curChild))) {
                          // If we are skipping keyed nodes then we add the key
                          // to a list so that it can be handled at the very end.
                          addKeyedRemoval(key);
                      } else {
                          // Only report the node as discarded if it is not keyed. We do this because
                          // at the end we loop through all keyed elements that were unmatched
                          // and then discard them in one final pass.
                          onNodeDiscarded(curChild);
                          if (curChild.firstChild) {
                              walkDiscardedChildNodes(curChild, skipKeyedNodes);
                          }
                      }

                      curChild = curChild.nextSibling;
                  }
              }
          }

          /**
           * Removes a DOM node out of the original DOM
           *
           * @param  {Node} node The node to remove
           * @param  {Node} parentNode The nodes parent
           * @param  {Boolean} skipKeyedNodes If true then elements with keys will be skipped and not discarded.
           * @return {undefined}
           */
          function removeNode(node, parentNode, skipKeyedNodes) {
              if (onBeforeNodeDiscarded(node) === false) {
                  return;
              }

              if (parentNode) {
                  parentNode.removeChild(node);
              }

              onNodeDiscarded(node);
              walkDiscardedChildNodes(node, skipKeyedNodes);
          }

          // // TreeWalker implementation is no faster, but keeping this around in case this changes in the future
          // function indexTree(root) {
          //     var treeWalker = document.createTreeWalker(
          //         root,
          //         NodeFilter.SHOW_ELEMENT);
          //
          //     var el;
          //     while((el = treeWalker.nextNode())) {
          //         var key = getNodeKey(el);
          //         if (key) {
          //             fromNodesLookup[key] = el;
          //         }
          //     }
          // }

          // // NodeIterator implementation is no faster, but keeping this around in case this changes in the future
          //
          // function indexTree(node) {
          //     var nodeIterator = document.createNodeIterator(node, NodeFilter.SHOW_ELEMENT);
          //     var el;
          //     while((el = nodeIterator.nextNode())) {
          //         var key = getNodeKey(el);
          //         if (key) {
          //             fromNodesLookup[key] = el;
          //         }
          //     }
          // }

          function indexTree(node) {
              if (node.nodeType === ELEMENT_NODE || node.nodeType === DOCUMENT_FRAGMENT_NODE$1) {
                  var curChild = node.firstChild;
                  while (curChild) {
                      var key = getNodeKey(curChild);
                      if (key) {
                          fromNodesLookup[key] = curChild;
                      }

                      // Walk recursively
                      indexTree(curChild);

                      curChild = curChild.nextSibling;
                  }
              }
          }

          indexTree(fromNode);

          function handleNodeAdded(el) {
              onNodeAdded(el);

              var curChild = el.firstChild;
              while (curChild) {
                  var nextSibling = curChild.nextSibling;

                  var key = getNodeKey(curChild);
                  if (key) {
                      var unmatchedFromEl = fromNodesLookup[key];
                      // if we find a duplicate #id node in cache, replace `el` with cache value
                      // and morph it to the child node.
                      if (unmatchedFromEl && compareNodeNames(curChild, unmatchedFromEl)) {
                          curChild.parentNode.replaceChild(unmatchedFromEl, curChild);
                          morphEl(unmatchedFromEl, curChild);
                      }
                  }

                  handleNodeAdded(curChild);
                  curChild = nextSibling;
              }
          }

          function cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey) {
              // We have processed all of the "to nodes". If curFromNodeChild is
              // non-null then we still have some from nodes left over that need
              // to be removed
              while (curFromNodeChild) {
                  var fromNextSibling = curFromNodeChild.nextSibling;
                  if ((curFromNodeKey = getNodeKey(curFromNodeChild))) {
                      // Since the node is keyed it might be matched up later so we defer
                      // the actual removal to later
                      addKeyedRemoval(curFromNodeKey);
                  } else {
                      // NOTE: we skip nested keyed nodes from being removed since there is
                      //       still a chance they will be matched up later
                      removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                  }
                  curFromNodeChild = fromNextSibling;
              }
          }

          function morphEl(fromEl, toEl, childrenOnly) {
              var toElKey = getNodeKey(toEl);

              if (toElKey) {
                  // If an element with an ID is being morphed then it will be in the final
                  // DOM so clear it out of the saved elements collection
                  delete fromNodesLookup[toElKey];
              }

              if (!childrenOnly) {
                  // optional
                  if (onBeforeElUpdated(fromEl, toEl) === false) {
                      return;
                  }

                  // update attributes on original DOM element first
                  morphAttrs(fromEl, toEl);
                  // optional
                  onElUpdated(fromEl);

                  if (onBeforeElChildrenUpdated(fromEl, toEl) === false) {
                      return;
                  }
              }

              if (fromEl.nodeName !== 'TEXTAREA') {
                morphChildren(fromEl, toEl);
              } else {
                specialElHandlers.TEXTAREA(fromEl, toEl);
              }

              if (!childrenOnly) {
                  onAfterElUpdated(fromEl);
              }
          }

          function morphChildren(fromEl, toEl) {
              var curToNodeChild = toEl.firstChild;
              var curFromNodeChild = fromEl.firstChild;
              var curToNodeKey;
              var curFromNodeKey;

              var fromNextSibling;
              var toNextSibling;
              var matchingFromEl;

              // walk the children
              outer: while (curToNodeChild) {
                  toNextSibling = curToNodeChild.nextSibling;
                  curToNodeKey = getNodeKey(curToNodeChild);

                  // walk the fromNode children all the way through
                  while (curFromNodeChild) {
                      fromNextSibling = curFromNodeChild.nextSibling;

                      if (curToNodeChild.isSameNode && curToNodeChild.isSameNode(curFromNodeChild)) {
                          curToNodeChild = toNextSibling;
                          curFromNodeChild = fromNextSibling;
                          continue outer;
                      }

                      curFromNodeKey = getNodeKey(curFromNodeChild);

                      var curFromNodeType = curFromNodeChild.nodeType;

                      // this means if the curFromNodeChild doesnt have a match with the curToNodeChild
                      var isCompatible = undefined;

                      if (curFromNodeType === curToNodeChild.nodeType) {
                          if (curFromNodeType === ELEMENT_NODE) {
                              // Both nodes being compared are Element nodes

                              if (curToNodeKey) {
                                  // The target node has a key so we want to match it up with the correct element
                                  // in the original DOM tree
                                  if (curToNodeKey !== curFromNodeKey) {
                                      // The current element in the original DOM tree does not have a matching key so
                                      // let's check our lookup to see if there is a matching element in the original
                                      // DOM tree
                                      if ((matchingFromEl = fromNodesLookup[curToNodeKey])) {
                                          if (fromNextSibling === matchingFromEl) {
                                              // Special case for single element removals. To avoid removing the original
                                              // DOM node out of the tree (since that can break CSS transitions, etc.),
                                              // we will instead discard the current node and wait until the next
                                              // iteration to properly match up the keyed target element with its matching
                                              // element in the original tree
                                              isCompatible = false;
                                          } else {
                                              // We found a matching keyed element somewhere in the original DOM tree.
                                              // Let's move the original DOM node into the current position and morph
                                              // it.

                                              // NOTE: We use insertBefore instead of replaceChild because we want to go through
                                              // the `removeNode()` function for the node that is being discarded so that
                                              // all lifecycle hooks are correctly invoked
                                              fromEl.insertBefore(matchingFromEl, curFromNodeChild);

                                              // fromNextSibling = curFromNodeChild.nextSibling;

                                              if (curFromNodeKey) {
                                                  // Since the node is keyed it might be matched up later so we defer
                                                  // the actual removal to later
                                                  addKeyedRemoval(curFromNodeKey);
                                              } else {
                                                  // NOTE: we skip nested keyed nodes from being removed since there is
                                                  //       still a chance they will be matched up later
                                                  removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                                              }

                                              curFromNodeChild = matchingFromEl;
                                          }
                                      } else {
                                          // The nodes are not compatible since the "to" node has a key and there
                                          // is no matching keyed node in the source tree
                                          isCompatible = false;
                                      }
                                  }
                              } else if (curFromNodeKey) {
                                  // The original has a key
                                  isCompatible = false;
                              }

                              isCompatible = isCompatible !== false && compareNodeNames(curFromNodeChild, curToNodeChild);
                              if (isCompatible) {
                                  // We found compatible DOM elements so transform
                                  // the current "from" node to match the current
                                  // target DOM node.
                                  // MORPH
                                  morphEl(curFromNodeChild, curToNodeChild);
                              }

                          } else if (curFromNodeType === TEXT_NODE || curFromNodeType == COMMENT_NODE) {
                              // Both nodes being compared are Text or Comment nodes
                              isCompatible = true;
                              // Simply update nodeValue on the original node to
                              // change the text value
                              if (curFromNodeChild.nodeValue !== curToNodeChild.nodeValue) {
                                  curFromNodeChild.nodeValue = curToNodeChild.nodeValue;
                              }

                          }
                      }

                      if (isCompatible) {
                          // Advance both the "to" child and the "from" child since we found a match
                          // Nothing else to do as we already recursively called morphChildren above
                          curToNodeChild = toNextSibling;
                          curFromNodeChild = fromNextSibling;
                          continue outer;
                      }

                      // No compatible match so remove the old node from the DOM and continue trying to find a
                      // match in the original DOM. However, we only do this if the from node is not keyed
                      // since it is possible that a keyed node might match up with a node somewhere else in the
                      // target tree and we don't want to discard it just yet since it still might find a
                      // home in the final DOM tree. After everything is done we will remove any keyed nodes
                      // that didn't find a home
                      if (curFromNodeKey) {
                          // Since the node is keyed it might be matched up later so we defer
                          // the actual removal to later
                          addKeyedRemoval(curFromNodeKey);
                      } else {
                          // NOTE: we skip nested keyed nodes from being removed since there is
                          //       still a chance they will be matched up later
                          removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                      }

                      curFromNodeChild = fromNextSibling;
                  } // END: while(curFromNodeChild) {}

                  // If we got this far then we did not find a candidate match for
                  // our "to node" and we exhausted all of the children "from"
                  // nodes. Therefore, we will just append the current "to" node
                  // to the end
                  if (curToNodeKey && (matchingFromEl = fromNodesLookup[curToNodeKey]) && compareNodeNames(matchingFromEl, curToNodeChild)) {
                      fromEl.appendChild(matchingFromEl);
                      // MORPH
                      morphEl(matchingFromEl, curToNodeChild);
                  } else {
                      var onBeforeNodeAddedResult = onBeforeNodeAdded(curToNodeChild);
                      if (onBeforeNodeAddedResult !== false) {
                          if (onBeforeNodeAddedResult) {
                              curToNodeChild = onBeforeNodeAddedResult;
                          }

                          if (curToNodeChild.actualize) {
                              curToNodeChild = curToNodeChild.actualize(fromEl.ownerDocument || doc);
                          }
                          fromEl.appendChild(curToNodeChild);
                          handleNodeAdded(curToNodeChild);
                      }
                  }

                  curToNodeChild = toNextSibling;
                  curFromNodeChild = fromNextSibling;
              }

              cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey);

              var specialElHandler = specialElHandlers[fromEl.nodeName];
              if (specialElHandler) {
                  specialElHandler(fromEl, toEl);
              }
          } // END: morphChildren(...)

          var morphedNode = fromNode;
          var morphedNodeType = morphedNode.nodeType;
          var toNodeType = toNode.nodeType;

          if (!childrenOnly) {
              // Handle the case where we are given two DOM nodes that are not
              // compatible (e.g. <div> --> <span> or <div> --> TEXT)
              if (morphedNodeType === ELEMENT_NODE) {
                  if (toNodeType === ELEMENT_NODE) {
                      if (!compareNodeNames(fromNode, toNode)) {
                          onNodeDiscarded(fromNode);
                          morphedNode = moveChildren(fromNode, createElementNS(toNode.nodeName, toNode.namespaceURI));
                      }
                  } else {
                      // Going from an element node to a text node
                      morphedNode = toNode;
                  }
              } else if (morphedNodeType === TEXT_NODE || morphedNodeType === COMMENT_NODE) { // Text or comment node
                  if (toNodeType === morphedNodeType) {
                      if (morphedNode.nodeValue !== toNode.nodeValue) {
                          morphedNode.nodeValue = toNode.nodeValue;
                      }

                      return morphedNode;
                  } else {
                      // Text node to something else
                      morphedNode = toNode;
                  }
              }
          }

          if (morphedNode === toNode) {
              // The "to node" was not compatible with the "from node" so we had to
              // toss out the "from node" and use the "to node"
              onNodeDiscarded(fromNode);
          } else {
              if (toNode.isSameNode && toNode.isSameNode(morphedNode)) {
                  return;
              }

              morphEl(morphedNode, toNode, childrenOnly);

              // We now need to loop over any keyed nodes that might need to be
              // removed. We only do the removal if we know that the keyed node
              // never found a match. When a keyed node is matched up we remove
              // it out of fromNodesLookup and we use fromNodesLookup to determine
              // if a keyed node has been matched up or not
              if (keyedRemovalList) {
                  for (var i=0, len=keyedRemovalList.length; i<len; i++) {
                      var elToRemove = fromNodesLookup[keyedRemovalList[i]];
                      if (elToRemove) {
                          removeNode(elToRemove, elToRemove.parentNode, false);
                      }
                  }
              }
          }

          if (!childrenOnly && morphedNode !== fromNode && fromNode.parentNode) {
              if (morphedNode.actualize) {
                  morphedNode = morphedNode.actualize(fromNode.ownerDocument || doc);
              }
              // If we had to swap out the from node with a new node because the old
              // node was not compatible with the target node then we need to
              // replace the old DOM node in the original DOM tree. This is only
              // possible if the original DOM node was part of a DOM tree which
              // we know is the case if it has a parent node.
              fromNode.parentNode.replaceChild(morphedNode, fromNode);
          }

          return morphedNode;
      };
  }

  var morphdom = morphdomFactory(morphAttrs);

  function randomId () {
    return (Math.random() * 10e6 | 0).toString(36) + (Math.random() * 10e6 | 0).toString(36)
  }

  function formatter (cid) {
    return (...message) => {
      return [
        `${performance.timeOrigin + performance.now()}.${randomId()}`,
        cid,
        message.join(' ')
      ].join('\t')
    }
  }

  function parse (data) {
    const nicks = new Map();
    const channels = new Map();
    const offers = new Map();
    const answers = new Map();
    const channel = name => channels.set(name, channels.get(name) || { users: new Set(), wall: [] }).get(name);
    const parsed = lines(data).map(parseLine).sort((a, b) => a.id < b.id ? -1 : a.id > b.id ? 1 : 0);
    const map = parsed.reduce((p, n) => (p[n.id] = n, p), {});
    parsed.forEach(msg => {
      switch (msg.command) {
        case 're': (map[msg.param].replies = map[msg.param].replies || []).push(msg); break
        case 'iam': nicks.set(msg.cid, msg.text); break
        case 'join': channel(msg.text).users.add(msg.cid); break
        case 'part': channel(msg.text).users.delete(msg.cid); break
        case 'offer': offers.set(msg.param, { cid: msg.cid, sdp: JSON.parse(msg.text) }); break
        case 'answer': answers.set(msg.param, { cid: msg.cid, sdp: JSON.parse(msg.text) }); break
        case 'msg': channel(msg.param).wall.push(msg); break
        default: console.error('Malformed message:', msg);
      }
    });
    return { nicks, channels, offers, answers }
  }

  function lines (data) {
    return [...data].map(chunk => chunk.split('\r\n')).flat(Infinity)
  }

  function merge (target, source) {
    const chunk = new Set();

    lines(source).forEach(line => {
      if (!target.has(line)) {
        target.add(line);
        chunk.add(line);
      }
    });

    return chunk
  }

  const parseLine = line => {
    let [id, cid, ...rest] = line.split('\t');
    rest = rest.join('\t').split(' ');
    let [command, param] = rest[0].split(':');
    let text = rest.slice(1).join(' ');
    return { id, cid, command, param, text }
  };

  const randomNick = () => {
    const nicks = [
      'john', 'anna', 'bob', 'suzanne', 'joe', 'mary', 'phil', 'julia', 'george', 'kate', 'chris', 'christine'
    ];
    return nicks[Math.random() * nicks.length | 0]
  };

  class State {
    constructor (app, data = '') {
      this.app = app;
      this.data = new Set(data ? [data] : [
        app.net.format('iam', randomNick()),
        app.net.format('join', '#garden'),
        app.net.format('msg:#garden', 'hello')
      ]);
      this.newPost = '';
      this.textareaRows = 1;
    }

    get merged () {
      return new Set([this.app.net.peers.map(peer => lines(peer.data)), ...this.data].flat(Infinity))
    }

    get view () {
      return parse(this.merged)
    }
  }

  function emit (target, name, data) {
    return target.dispatchEvent(new CustomEvent(name, { detail: data }))
  }

  function once (emitter, name) {
    return new Promise(resolve => emitter.addEventListener(name, resolve, { once: true }))
  }

  function on (emitter, name, until) {
    let resolve = () => {}, reject = () => {};

    let needle = 0;

    const listener = event => {
      listener.events.push(event);
      resolve(event);
    };

    listener.events = [];

    listener.end = event => {
      emitter.removeEventListener(name, listener);
      listener.ended = true;
      reject(event);
    };

    listener[Symbol.asyncIterator] = async function * () {
      // send events in queue
      while (needle < listener.events.length) {
        yield listener.events[needle++];
      }
      while (!listener.ended) {
        try {
          yield new Promise((...callbacks) => ([resolve, reject] = callbacks));
        } catch {
          return
        }
      }
    };

    emitter.addEventListener(name, listener);

    if (until) once(emitter, until).then(listener.end);

    return listener
  }

  function secs (n = Math.random() * 10) {
    return new Promise(resolve => setTimeout(resolve, n * 1000))
  }

  function copy (obj) {
    return JSON.parse(JSON.stringify(obj))
  }

  const OPTIONS = {
    iceServers: []
  };

  class Peer extends EventTarget {
    constructor (opts = OPTIONS) {
      super();
      this.cid = null;
      this.data = new Set();
      this.connected = false;
      this.connection = new RTCPeerConnection(opts);
      this.connection.onconnectionstatechange = () => {
        switch (this.connection.connectionState) {
          case 'disconnected':
          case 'failed':
          case 'closed':
            this.close();
        }
      };
    }

    close () {
      this.connected = false;
      try { this.connection.close(); } catch {}
      try { this.messages.end(); } catch {}
      emit(this, 'close');
    }

    send (data) {
      const chunk = merge(this.data, data);
      if (chunk.size) try { this.channel.send([...chunk].join('\r\n')); } catch {}
    }

    async createOffer () {
      this.openChannel(this.connection.createDataChannel('data'));
      await once(this.connection, 'negotiationneeded');
      const offer = await this.connection.createOffer();
      return this.createSignal(offer)
    }

    receiveAnswer (answer) {
      this.cid = answer.cid;
      return this.connection.setRemoteDescription(answer)
    }

    async createAnswer (offer) {
      this.cid = offer.cid;
      await this.connection.setRemoteDescription(offer);
      const answer = await this.connection.createAnswer();
      once(this.connection, 'datachannel').then(({ channel }) => this.openChannel(channel));
      return this.createSignal({ ...offer, ...copy(answer) })
    }

    async createSignal (signal) {
      signal.sdp = signal.sdp.replace(/a=ice-options:trickle\s\n/g, '');
      await this.connection.setLocalDescription(signal);
      await Promise.race([once(this.connection, 'icecandidate'), secs(30)]);
      return { ...signal, ...copy(this.connection.localDescription) }
    }

    async openChannel (channel) {
      this.channel = channel;
      this.messages = on(this.channel, 'message', 'close');
      await once(this.channel, 'open');
      this.format = formatter(this.cid);
      this.connected = true;
      emit(this, 'open');
      for await (const { data } of this.messages) {
        const chunk = merge(this.data, [data]);
        if (chunk.size) emit(this, 'data', chunk);
      }
      this.close();
    }
  }

  let base = window.base || 'http://localhost';

  const json = res => res.json();
  const headers = {
    Accept: 'application/json',
    Authorization: `Bearer ${window.token}`
  };
  const get = (url, opts = {}) => fetch(url, Object.assign(opts, { headers })).then(json);
  const post = (url, data) => {
    const body = new FormData();
    Object.keys(data).forEach(key => body.append(key, data[key]));
    return get(url, { method: 'POST', body })
  };

  const getNextOffer = (not) => get(`${base}/?not=${not}`);

  // server php encrypts id and we send it back on every request on header

  const sendOffer = sdp => post(base, { type: sdp.type, sdp: sdp.sdp });
  const sendAnswer = sdp => post(base, { id: sdp.id, type: sdp.type, sdp: sdp.sdp });
    // if (!notKnownPeer(id)) throw new Error('Answer aborted, known peer')
  // }
  const getAnswer = offer => get(`${base}/?id=${offer.id}`);

  const pollForAnswer = async (offer, retries = 10) => {
    for (let i = 0, answer; i < retries; i++) {
      try {
        answer = await getAnswer(offer);
        return answer
      } catch (error) {
        await secs();
      }
    }
    throw new Error('Waiting for answer failed: Max retries reached')
  };

  const OPTIONS$1 = {
    maxPeers: 6
  };

  class Net extends EventTarget {
    constructor (app, opts = OPTIONS$1) {
      super();
      this.cid = window.token.slice(-5);
      this.app = app;
      this.opts = opts;
      this.peers = [];
      this.format = formatter(this.cid);
    }

    async connect () {
      this.make('offer');
      await secs(); // shuffle entry for answers. useful when dev
      this.make('answer');
    }

    async addPeer (peer) {
      let msg;
      this.peers.push(peer);
      msg = peer.format('join', '#garden');
      this.app.state.data.add(msg);
      this.broadcast([msg], peer);
      peer.send(this.app.state.merged);
      emit(this, 'peer', peer);
      for await (const { detail: data } of on(peer, 'data', 'close')) {
        const { offers } = parse(data);
        const offer = offers.get(this.cid);
        if (offer) this.answerTo(offer);

        this.broadcast(data, peer);
        emit(this, 'data', { data, peer });
      }
      this.peers.splice(this.peers.indexOf(peer), 1);
      msg = peer.format('part', '#garden');
      this.app.state.data.add(msg);
      this.broadcast([msg], peer);
      emit(this, 'peer', peer);
    }

    async broadcast (data, peer = {}) {
      this.peers.filter(p => p.cid !== peer.cid).forEach(peer => peer.send(data));
    }

    async lessThanMaxPeers () {
      while (this.peers.length >= this.opts.maxPeers) {
        await once(this, 'peer');
      }
      return true
    }

    async make (type) {
      while (true) {
        await this.lessThanMaxPeers();
        await this.createPeer(type);
        await secs(3 + this.peers.length ** 3);
      }
    }

    async offerTo (cid) {
      if (this.peers.map(peer => peer.cid).includes(cid)) {
        throw new Error(`Connection to ${cid} aborted, already connected.`)
      }

      const peer = new Peer();
      const offer = await peer.createOffer();
      const msg = this.format(`offer:${cid}`, JSON.stringify(offer.sdp));
      this.broadcast([msg]);
      try {
        for await (const { detail: { data } } of on(this, 'data')) {
          const { answers } = parse(data);
          const answer = answers.get(this.cid);
          if (answer) {
            await peer.receiveAnswer({ type: 'answer', ...answer });
            await Promise.race([once(peer, 'open'), secs(30)]);
            if (!peer.connected) throw new Error(`Connection timeout [by offer to ${cid}].`)
            return this.addPeer(peer)
          }
        }
      } catch (error) {
        console.error(error);
      }
      peer.close();
    }

    async answerTo (offer) {
      const peer = new Peer();
      try {
        const answer = await peer.createAnswer({ type: 'offer', ...offer });
        const msg = this.format(`answer:${offer.cid}`, JSON.stringify(answer.sdp));
        this.broadcast([msg]);
        await Promise.race([once(peer, 'open'), secs(30)]);
        if (!peer.connected) throw new Error(`Connection timeout [by answer to ${offer.cid}].`)
        return this.addPeer(peer)
      } catch (error) {
        console.error(error);
      }
      peer.close();
    }

    async createPeer (type) {
      const peer = new Peer();
      try {
        if (type === 'offer') {
          const offer = await sendOffer(await peer.createOffer());
          const answer = await pollForAnswer(offer);
          await peer.receiveAnswer(answer);
        } else if (type === 'answer') {
          const known = this.peers.map(peer => peer.cid).join();
          const offer = await getNextOffer(known);
          const answer = await peer.createAnswer(offer);
          await sendAnswer(answer);
        }
        await Promise.race([once(peer, 'open'), secs(30)]);
        if (!peer.connected) throw new Error(`Connection timeout [${type}].`)
        return this.addPeer(peer)
      } catch (error) {
        console.error(error);
      }
      peer.close();
    }
          // public/private key generated on init
          // peer broadcasts public key
          // other peers use public key to encrypt a message(offer) to be whispered
          // only peer with private key can decrypt the message
          // channel.offer should contain an id known only to this peer
          // so that we can use it to delete the offer on completion/failure
          //await peer.connect(await this.handshake(transport, await peer.open()))

          // await this.handshake(transport, await peer.open(await this.handshake(transport)))
          // await peer.connect()

    // async handshake (transport, signal) {
    //   if (signal) {
    //     if (signal.type === 'offer') {
    //       const offer = await transport.sendOffer(signal)
    //       const answer = await transport.waitForAnswer(offer)
    //       return answer
    //     } else if (signal.type === 'answer') {
    //       await transport.sendAnswer(signal)
    //     }
    //   } else {
    //     const known = this.peers.map(peer => peer.cid).join()
    //     const offer = await transport.getNextOffer(known)
    //     return offer
    //   }
    // }

    // async swarmHandshake (signal) {
    //   if (signal) {
    //     if (signal.type === 'offer') {
    //       // use other public key to encrypt offer signal
    //       // broadcast encrypted offer signal to swarm
    //       // await for answer signal and examine unique id
    //       // decrypt answer signal using private key
    //       // return answer signal
    //     } else if (signal.type === 'answer') {
    //       // use other public key to encrypt answer signal
    //       // broadcast encrypted answer signal to swarm
    //     }
    //   } else {
    //     // get offer signal picked from message
    //     // decrypt offer signal using private key
    //     // return offer signal
    //   }
    // }
  }

  // loop: for await (const event of channel.event()) {
  //   switch (event.type) {
  //     case 'open':
  //       //
  //       break loop
  //     case 'close':
  //       //
  //       break loop
  //     case 'message':
  //       //
  //       break
  //   }
  // }

  class App {
    constructor (el) {
      this.el = el;
      this.app = this;
      this.net = new Net(this);
      this.state = new State(this, this.load());
      this.ui = $(UI, this);
      this.net.addEventListener('peer', () => this.render());
      this.net.addEventListener('data', () => this.render());
      document.addEventListener('render', () => this.render());
    }

    dispatch (...message) {
      message = this.net.format(...message);
      console.log('dispatch', message);
      this.state.data.add(message);
      this.net.broadcast([message], this.net);
      // this.dispatchEvent(new CustomEvent('data', { detail: data }))
    }

    load () {
      return localStorage.data || ''
    }

    save () {
      localStorage.data = [...this.state.data].join('\r\n');
    }

    offerTo (cid) {
      this.net.offerTo(cid);
    }

    onrender (el) {
      if (el instanceof Element) {
        const expr = el.getAttribute('onrender');
        if (expr) {
          const fn = new Function(expr);
          fn.call(el);
        }
      }
    }

    render () {
      const html = this.ui.toString(true);
      morphdom(this.el, html, {
        onNodeAdded: this.onrender,
        onElUpdated: this.onrender,
        onAfterElUpdated: this.onrender
      });
    }
  }

  class UI {
    constructor () {
      this.isBottom = true;
    }

    template () {
      const view = this.state.view;
      const channel = view.channels.get('#garden');
      const peers = this.app.net.peers.map(peer => peer.cid);
      prevUser = null;
      return `
      <div class="app">
        <div class="side">
          <div class="peers">
            ${ channel ? $.map([...channel.users].filter(cid => cid !== this.app.net.cid), cid =>
              `<div
                  class="peer ${ $.class({ direct: peers.includes(cid) }) }"
                  onclick="${ this.offerTo }('${cid}')">
                ${view.nicks.get(cid) || cid}
              </div>`) : '' }
          </div>
        </div>
        <div class="main" onscroll="${ this.checkScrollBottom }()" onrender="${ this.scrollToBottom }()">
          ${ $(ChatArea, { view, target: '#garden', app: this.app, state: this.state }) }
        </div>
      </div>
    `
    }

    checkScrollBottom () {
      this.isBottom = Math.round(this.scrollTop + this.clientHeight) >= this.scrollHeight - 50;
      return false
    }

    scrollToBottom () {
      if (this.isBottom) this.scrollTop = this.scrollHeight;
      return false
    }
  }

  class ChatArea {
    template () {
      const view = this.view;
      const channel = this.view.channels.get(this.target);
      return `
      <div class="chatarea">
        <div class="wall">
          ${ channel ? $.map(channel.wall, post => $(Post, post, { view })) : ''}
        </div>
        <div class="chatbar">
          <div class="target">${this.app.net.cid}</div>
          <div class="nick">${ view.nicks.get(this.app.net.cid) }</div>
          <textarea
            class="${ $.class({ pre: this.state.textareaRows > 1 }) }"
            onkeydown="${ this.processKeyDown }(event)"
            oninput="${ this.processInput }()"
            rows=${ this.state.textareaRows }>${ this.state.newPost }</textarea>
          <button onclick="${ this.createPost }()">send</button>
          <div class="target">${this.target}</div>
        </div>
      </div>
    `
    }

    createPost () {
      if (!this.state.newPost.length) return
      this.app.dispatch('msg:#garden', this.state.newPost);
      this.state.newPost = '';
      this.state.textareaRows = 1;
    }

    processKeyDown (event) {
      if (event.which === 13) {
        if (event.ctrlKey === true) {
          const pos = this.selectionStart;
          this.value = this.value.slice(0, pos) + '\n' + this.value.slice(pos);
          this.processInput();
          this.selectionStart = this.selectionEnd = pos + 1;
        } else {
          event.preventDefault();
          this.createPost();
          return false
        }
      } else {
        return false
      }
    }

    processInput (arg) {
      const rows = this.state.textareaRows;
      this.state.newPost = this.value;
      const computed = window.getComputedStyle(this.el);
      const newRows = Math.max(
        this.state.newPost.split('\n').length,
        Math.floor(this.scrollHeight / (parseFloat(computed.lineHeight)))
      );
      if (newRows === rows) return false
      this.state.textareaRows = newRows;
    }
  }

  let prevUser, prevTime;

  class Post {
    // ({ meta, user, time, text, replies = [] }) => `
    template () {
      const lastPrevUser = prevUser;
      const lastPrevTime = lastPrevUser !== this.cid ? 0 : prevTime;
      prevUser = this.cid;
      prevTime = parseInt(this.time);
      return `
      <br>
      <div class="post">
        ${ lastPrevUser !== this.cid ? `<a class="user" href="/#~${this.cid}">${htmlescape(this.view.nicks.get(this.cid))}:</a>` : `` }
        ${ prevTime - lastPrevTime > 1000 * 60 ? `
        <info>
          <!-- <time>${new Date(+this.time).toLocaleString()}</time> -->
          <a href="#">reply</a>
        </info>` : '' }
        <p class="${ this.text.includes('\n') ? 'pre' : '' }">${htmlescape(this.text, this.text.includes('\n'))}</p>
        ${ $.map(this.replies || [], post => $(Post, { view: this.view, ...post })) }
      </div>
    `
    }
  }

  function htmlescape (text, initialSpace) {
    text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;');
    if (initialSpace) text = text.replace(/ /,'&nbsp;');
    return text
  }

          // ${ this.privateOpen ? `
          //   <div class="private">
          //     ${ $(ChatArea, this.private[this.privatePeer.cid]) }
          //   </div>
          // ` : `` }

              // ${ $.map(this.app.net.peers.map(peer => [peer.cid, this.meta.getUser(peer.cid)]).concat(Object.keys(this.meta.nicks)
              //   .filter(pcid => !this.app.net.peers.map(peer => peer.cid).includes(pcid) && pcid !== cid)
              //   .map(pcid => [pcid, this.meta.getUser(pcid), true]))
              //   .sort((a, b) => a[1] > b[1] ? 1 : a[1] < b[1] ? -1 : 0)
              // , ([pcid, nick, inNetwork]) =>
              //   `<div class="peer ${inNetwork ? 'in-network' : ''}" ${inNetwork ? `data-cid="${pcid}" onclick="${ this.offerToPeer }(this.dataset.cid)"` : ''}>${htmlescape(nick)}</div>`) }

  const app = window.app = new App(container);
  app.net.connect();
  app.render();

}());
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