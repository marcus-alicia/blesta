/*
* jquery.innershiv: Fixes HTML5 compatibility for jQuery's ajax calls.
* It seamlessly combines the innershiv script with jQuery ajax methods.
* No changes need to be made to your jQuery calls.
* Credit goes to the original idea here: http://tomcoote.co.uk/javascript/ajax-html5-in-ie/
*/
window.jQuery && jQuery.ajaxSetup({
dataFilter: function(data, dataType) {
return (dataType === 'html')
? innerShiv(data, false, false)
: data;
}
});

/* innerShiv: makes HTML5shim work on innerHTML & jQuery
* http://jdbartlett.github.com/innershiv
*
* This program is free software. It comes without any warranty, to
* the extent permitted by applicable law. You can redistribute it
* and/or modify it under the terms of the Do What The Fuck You Want
* To Public License, Version 2, as published by Sam Hocevar. See
* http://sam.zoy.org/wtfpl/COPYING for more details.
*/
window.innerShiv = (function () {
var div;
var doc = document;
var needsShiv;

// Array of elements that are new in HTML5
var html5 = 'abbr article aside audio canvas datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video'.split(' ');

// Used to idiot-proof self-closing tags
function fcloseTag(all, front, tag) {
return (/^(?:area|br|col|embed|hr|img|input|link|meta|param)$/i).test(tag) ? all : front + '></' + tag + '>';
}

return function (
html, /* string */
returnFrag, /* optional false bool */
stripScripts /* optional false bool */
) {
if (!div) {
div = doc.createElement('div');

// needsShiv if can't use HTML5 elements with innerHTML outside the DOM
div.innerHTML = '<nav></nav>';
needsShiv = div.childNodes.length !== 1;

if (needsShiv) {
// MSIE allows you to create elements in the context of a document
// fragment. Jon Neal first discovered this trick and used it in his
// own shimprove: http://www.iecss.com/shimprove/
var shimmedFrag = doc.createDocumentFragment();
var i = html5.length;
while (i--) {
shimmedFrag.createElement(html5[i]);
}

shimmedFrag.appendChild(div);
}
}

// Trim whitespace to avoid unexpected text nodes in return data:
html = html.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
// Strip any scripts:
if (stripScripts !== false)
html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
// Fix misuses of self-closing tags:
html = html.replace(/(<([\w:]+)[^>]*?)\/>/g, fcloseTag);

// Fix for using innerHTML in a table
var tabled;
if (tabled = html.match(/^<(tbody|tr|td|th|col|colgroup|thead|tfoot)[\s\/>]/i)) {
div.innerHTML = '<table>' + html + '</table>';
} else {
div.innerHTML = html;
}

// Avoid returning the tbody or tr when fixing for table use
var scope;
if (tabled) {
scope = div.getElementsByTagName(tabled[1])[0].parentNode;
} else {
scope = div;
}

// If not in jQuery return mode, return child nodes array
if (returnFrag === false) {
return scope.childNodes;
}

// ...otherwise, build a fragment to return
var returnedFrag = doc.createDocumentFragment();
var j = scope.childNodes.length;
while (j--) {
returnedFrag.appendChild(scope.firstChild);
}

return returnedFrag;
};
}());