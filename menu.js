var titleElm = document.getElementById('title');
var menuElm = document.getElementById('file-menu');

var nameSuggestions = [
  "Grocery list",
  "Todo",
  "World takeover plan",
  "Kid names"
];

function pickRandomName() {
  return nameSuggestions[
      Math.round(Math.random() * (nameSuggestions.length - 1))];
}

function isMenuVisible() {
  return menuElm.style.visibility == 'visible';
}

function setMenuVisible(vis) {
  menuElm.style.visibility = vis ? 'visible' : 'hidden';
}

title.addEventListener('click', function(e) {
  if (!isMenuVisible()) {
    setMenuVisible(true);
    e.stopPropagation();
  }
}, false);

document.addEventListener('click', function() {
  if (isMenuVisible())
    setMenuVisible(false);
}, false);

function handleMenuItem(itemId, handler) {
  document.getElementById(itemId).addEventListener('click', function(e) {
    try {
      setMenuVisible(false);
      handler(e);
    } finally {
      return false;
    }
  }, false);
}

handleMenuItem('new-note-item', function() {
  var name = prompt("Enter a name for your new note:", pickRandomName());
  if (!name)
    return;

  location.href = '?new=' + encodeURIComponent(name);
});

handleMenuItem('rename-note-item', function() {
  var name = prompt("Rename to what?", noteName);
  if (!name)
    return;

  location.href = '?rename=' + encodeURIComponent(name);
});

handleMenuItem('delete-note-item', function() {
  if (!confirm('Really delete current note?'))
    return;

  location.href = '?delete=1';
});
