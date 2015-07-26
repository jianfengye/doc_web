# 如何在atom中设置自定义脚本并且绑定快捷键

keymap.cson

'atom-text-editor':
  'cmd-1': 'custom:print-exit'


init.coffee

atom.commands.add 'atom-text-editor', 'custom:print-exit', ->
  editor = @getModel()
  editor.insertText('print_r($a);exit;')

https://atom.io/docs/latest/behind-atom-keymaps-in-depth
