'use strict';
const fs = require('fs');
const nodeScript = process.argv.shift();
const currentScript = process.argv.shift();

const title = process.argv.shift();
const slug = title.toLowerCase().replace(/\s+/g, '-');

const cwd = process.env.INIT_CWD;
const pagesDir = cwd + '/pages';
if (!fs.existsSync(pagesDir)){
  fs.mkdirSync(pagesDir);
}
const pageDir = pagesDir + '/' + slug;
if (!fs.existsSync(pageDir)){
  fs.mkdirSync(pageDir);
}

const filePath = pageDir + '/' + slug + '.page';
function camelize(str) {
  return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(word, index) {
    return word.toUpperCase();
  }).replace(/\s+/g, '');
}
// Component
const componentName = camelize(title) + 'Page';

const component =`
import { Component } from '@angular/core';
 import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

@Component({
  standalone: true,
  imports: [CommonModule],
  templateUrl: './${slug}.page.html',
  styleUrl: './${slug}.page.scss'
})

export class ${componentName} extends TetraPage {
  override title = '${title}';
}`;
fs.writeFileSync(filePath.trim() + '.ts',component)
// Template
const template ='<h1 class="page-title">{{title}}</h1>';
fs.writeFileSync(filePath.trim() + '.html',template)

// SCSS
const scss ='@import "../../tetra/app/page/page.component.scss";';
fs.writeFileSync(filePath.trim() + '.scss', scss);
