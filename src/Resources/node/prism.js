const { readFileSync } = require('fs');
const Prism = require('prismjs');
const [language, path] = process.argv.slice(2);

require('prismjs/components/')([language]);

const result = Prism.highlight(readFileSync(path, 'utf8'), Prism.languages[language], language);

process.stdout.end(result);
