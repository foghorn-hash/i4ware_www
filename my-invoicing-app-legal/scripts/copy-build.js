const fs = require('fs');
const path = require('path');

const srcDir = path.resolve(__dirname, '..', 'build', 'static');
const destDir = path.resolve(__dirname, '..', '..', 'legal-react-app', 'static');

if (!fs.existsSync(srcDir)) {
  console.error(`Build static directory not found: ${srcDir}`);
  process.exit(1);
}

const destCssDir = path.join(destDir, 'css');
const destJsDir = path.join(destDir, 'js');
fs.mkdirSync(destCssDir, { recursive: true });
fs.mkdirSync(destJsDir, { recursive: true });

function cleanDir(dir) {
  if (fs.existsSync(dir)) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
      fs.unlinkSync(path.join(dir, file));
    }
  }
}

cleanDir(destCssDir);
cleanDir(destJsDir);

function copyDir(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  const entries = fs.readdirSync(src, { withFileTypes: true });

  for (const entry of entries) {
    const srcPath = path.join(src, entry.name);
    const destPath = path.join(dest, entry.name);

    if (entry.isDirectory()) {
      copyDir(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
      console.log(`Copied: ${entry.name} -> ${path.relative(path.resolve(__dirname, '..', '..'), destPath)}`);
    }
  }
}

copyDir(srcDir, destDir);
console.log('Successfully updated legal-react-app static assets!');
