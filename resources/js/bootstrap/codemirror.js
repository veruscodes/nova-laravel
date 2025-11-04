import CodeMirror from 'codemirror'

import 'codemirror/mode/markdown/markdown'
import 'codemirror/mode/javascript/javascript'
import 'codemirror/mode/php/php'
import 'codemirror/mode/ruby/ruby'
import 'codemirror/mode/shell/shell'
import 'codemirror/mode/sass/sass'
import 'codemirror/mode/yaml/yaml'
import 'codemirror/mode/yaml-frontmatter/yaml-frontmatter'
import 'codemirror/mode/nginx/nginx'
import 'codemirror/mode/xml/xml'
import 'codemirror/mode/vue/vue'
import 'codemirror/mode/dockerfile/dockerfile'
import 'codemirror/keymap/vim'
import 'codemirror/mode/sql/sql'
import 'codemirror/mode/twig/twig'
import 'codemirror/mode/htmlmixed/htmlmixed'

export function setupCodeMirror() {
  CodeMirror.defineMode('htmltwig', function (config, parserConfig) {
    return CodeMirror.overlayMode(
      CodeMirror.getMode(config, parserConfig.backdrop || 'text/html'),
      CodeMirror.getMode(config, 'twig')
    )
  })

  CodeMirror.defineOption('autoRefresh', false, function (editor, value) {
    if (editor.state.autoRefresh) {
      stopListeningToCodeMirrorAutoRefresh(editor, editor.state.autoRefresh)
      editor.state.autoRefresh = null
    }
    if (value && editor.display.wrapper.offsetHeight == 0)
      startListeningToCodeMirrorAutoRefresh(
        editor,
        (editor.state.autoRefresh = { delay: value.delay || 250 })
      )
  })

  function startListeningToCodeMirrorAutoRefresh(editor, state) {
    function check() {
      if (editor.display.wrapper.offsetHeight) {
        stopListeningToCodeMirrorAutoRefresh(editor, state)
        if (
          editor.display.lastWrapHeight != editor.display.wrapper.clientHeight
        ) {
          editor.refresh()
        }
      } else {
        state.timeout = setTimeout(check, state.delay)
      }
    }
    state.timeout = setTimeout(check, state.delay)
    state.hurry = function () {
      clearTimeout(state.timeout)
      state.timeout = setTimeout(check, 50)
    }
    CodeMirror.on(window, 'mouseup', state.hurry)
    CodeMirror.on(window, 'keyup', state.hurry)
  }

  function stopListeningToCodeMirrorAutoRefresh(editor, state) {
    clearTimeout(state.timeout)
    CodeMirror.off(window, 'mouseup', state.hurry)
    CodeMirror.off(window, 'keyup', state.hurry)
  }
}
