import fs from 'fs'
import path from 'path'

const root = process.cwd()
const sourceDir = path.join(root, 'articles')
const outputDir = path.join(root, 'content-importer', 'content')

const escapeHtml = (value) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')

const inline = (value) => {
  let result = escapeHtml(value)
  result = result.replace(/`([^`]+)`/g, '<code>$1</code>')
  result = result.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2">$1</a>')
  result = result.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
  return result
}

const parseFrontmatter = (text) => {
  const match = text.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/)
  if (!match) throw new Error('Missing frontmatter')
  const slugMatch = match[1].match(/^slug:\s*"([^"]+)"/m)
  const slug = slugMatch && slugMatch[1]
  if (!slug) throw new Error('Missing slug')
  return { slug, body: match[2] }
}

const render = (body) => {
  const lines = body.replace(/\r\n/g, '\n').split('\n')
  const output = []
  let paragraph = []
  let listType = null
  let inCode = false
  let codeLanguage = ''
  let codeLines = []

  const closeParagraph = () => {
    if (paragraph.length) {
      output.push(`<p>${inline(paragraph.join(' ').trim())}</p>`)
      paragraph = []
    }
  }
  const closeList = () => {
    if (listType) {
      output.push(`</${listType}>`)
      listType = null
    }
  }
  const closeCode = () => {
    if (inCode) {
      const className = codeLanguage ? ` class="language-${escapeHtml(codeLanguage)}"` : ''
      output.push(`<pre><code${className}>${escapeHtml(codeLines.join('\n'))}</code></pre>`)
      inCode = false
      codeLanguage = ''
      codeLines = []
    }
  }

  for (const line of lines) {
    const fence = line.match(/^(```|~~~)\s*([\w+-]*)\s*$/)
    if (fence) {
      if (inCode) closeCode()
      else {
        closeParagraph()
        closeList()
        inCode = true
        codeLanguage = fence[2]
      }
      continue
    }
    if (inCode) {
      codeLines.push(line)
      continue
    }
    if (!line.trim()) {
      closeParagraph()
      closeList()
      continue
    }
    if (/^# /.test(line)) {
      closeParagraph()
      closeList()
      continue
    }
    const heading = line.match(/^(#{2,4})\s+(.+)$/)
    if (heading) {
      closeParagraph()
      closeList()
      const level = Math.min(4, heading[1].length)
      output.push(`<h${level}>${inline(heading[2])}</h${level}>`)
      continue
    }
    const unordered = line.match(/^[-*]\s+(.+)$/)
    const ordered = line.match(/^\d+[.)]\s+(.+)$/)
    if (unordered || ordered) {
      closeParagraph()
      const nextType = unordered ? 'ul' : 'ol'
      if (listType !== nextType) {
        closeList()
        listType = nextType
        output.push(`<${listType}>`)
      }
      output.push(`<li>${inline((unordered || ordered)[1])}</li>`)
      continue
    }
    if (/^>\s?/.test(line)) {
      closeParagraph()
      closeList()
      output.push(`<blockquote>${inline(line.replace(/^>\s?/, ''))}</blockquote>`)
      continue
    }
    closeList()
    paragraph.push(line.trim())
  }
  closeParagraph()
  closeList()
  closeCode()
  return `${output.join('\n')}\n`
}

for (const filename of fs.readdirSync(sourceDir).filter((name) => name.endsWith('.md') && name !== 'README.md')) {
  const source = fs.readFileSync(path.join(sourceDir, filename), 'utf8')
  const { body } = parseFrontmatter(source)
  fs.writeFileSync(path.join(outputDir, filename.replace(/\.md$/, '.html')), render(body))
}

console.log('Synchronized article Markdown to importer HTML.')
