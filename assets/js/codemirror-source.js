/**
 * Local CodeMirror 6 browser bundle for the Scripts Library.
 * Build with: npm install && npm run build:editor
 * The existing textarea remains the source of truth for HTML forms and Copy.
 */
import { EditorState } from '@codemirror/state';
import {
  EditorView, keymap, lineNumbers, highlightActiveLine,
  highlightActiveLineGutter, drawSelection, highlightSpecialChars,
  rectangularSelection, crosshairCursor
} from '@codemirror/view';
import { javascript } from '@codemirror/lang-javascript';
import { bracketMatching, indentOnInput, syntaxHighlighting, HighlightStyle } from '@codemirror/language';
import { tags as t } from '@lezer/highlight';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { highlightSelectionMatches, searchKeymap } from '@codemirror/search';

const theme = EditorView.theme({
  '&': { color: '#d4d4d4', backgroundColor: '#1e1e1e', border: '1px solid #3c3c3c', borderRadius: '9px', fontSize: '13px' },
  '.cm-content': { fontFamily: 'Consolas, "Cascadia Code", monospace', minHeight: '350px', caretColor: '#aeafad', padding: '10px 0' },
  '.cm-scroller': { overflow: 'auto', maxHeight: '68vh', fontFamily: 'Consolas, "Cascadia Code", monospace' },
  '.cm-gutters': { backgroundColor: '#1e1e1e', color: '#858585', borderRight: '1px solid #333333' },
  '.cm-activeLine': { backgroundColor: '#2a2d2e70' },
  '.cm-activeLineGutter': { backgroundColor: '#252526' },
  '.cm-cursor, .cm-dropCursor': { borderLeftColor: '#aeafad' },
  '.cm-selectionBackground, &.cm-focused .cm-selectionBackground, ::selection': { backgroundColor: '#264f78 !important' },
  '.cm-matchingBracket': { backgroundColor: '#3a3d41', outline: '1px solid #888888' },
  '.cm-searchMatch': { backgroundColor: '#613214', outline: '1px solid #b78848' },
  '.cm-focused': { outline: '1px solid #007acc' }
}, { dark: true });

// VS Code Dark+ inspired token colors; bundled locally, no CDN required.
const vscodeDarkPlus = HighlightStyle.define([
  { tag: [t.keyword, t.modifier, t.definitionKeyword], color: '#569cd6' },
  { tag: [t.controlKeyword, t.operatorKeyword], color: '#c586c0' },
  { tag: [t.string, t.special(t.string)], color: '#ce9178' },
  { tag: [t.comment, t.lineComment, t.blockComment, t.docComment], color: '#6a9955' },
  { tag: [t.number, t.bool, t.null], color: '#b5cea8' },
  { tag: [t.function(t.variableName), t.function(t.propertyName)], color: '#dcdcaa' },
  { tag: [t.variableName, t.definition(t.variableName)], color: '#9cdcfe' },
  { tag: [t.propertyName], color: '#9cdcfe' },
  { tag: [t.typeName, t.className, t.namespace], color: '#4ec9b0' },
  { tag: [t.operator, t.punctuation, t.bracket], color: '#d4d4d4' },
  { tag: [t.invalid], color: '#f44747' }
]);
const tokenTheme = syntaxHighlighting(vscodeDarkPlus);

function mountEditor(textarea) {
  const readonly = textarea.readOnly;
  const mount = document.createElement('div');
  mount.className = 'scripts-codemirror-mount';
  textarea.insertAdjacentElement('afterend', mount);
  const extensions = [
    lineNumbers(), highlightActiveLineGutter(), highlightSpecialChars(),
    history(), drawSelection(), EditorState.allowMultipleSelections.of(true),
    indentOnInput(), bracketMatching(), highlightActiveLine(),
    highlightSelectionMatches(), rectangularSelection(), crosshairCursor(),
    javascript(), theme, tokenTheme,
    EditorState.readOnly.of(readonly), EditorView.editable.of(!readonly),
    EditorView.updateListener.of((update) => {
      if (update.docChanged) textarea.value = update.state.doc.toString();
    })
  ];
  if (!readonly) extensions.push(keymap.of([indentWithTab, ...defaultKeymap, ...historyKeymap, ...searchKeymap]));
  else extensions.push(keymap.of([...searchKeymap]));
  const view = new EditorView({ state: EditorState.create({doc: textarea.value, extensions}), parent: mount });
  textarea.hidden = true;
  textarea.classList.add('script-code-original');
  // Defensive sync for normal form submission and button-based copy operations.
  const form = textarea.form;
  if (form) form.addEventListener('submit', () => { textarea.value = view.state.doc.toString(); });
}

function activate() {
  document.querySelectorAll('#script-code').forEach((textarea) => {
    if (!(textarea instanceof HTMLTextAreaElement) || textarea.hidden) return;
    try { mountEditor(textarea); }
    catch (error) { console.error('CodeMirror initialization failed; falling back to plain editor.', error); }
  });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', activate);
else activate();
