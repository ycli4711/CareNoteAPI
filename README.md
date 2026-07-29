# CareNoteAPI

CareNoteAPI 是基于 Laravel、Inertia React 和 TypeScript 的应用骨架，结构与
SummerClosetAPI 的初始技术栈保持一致，不包含具体业务代码。

## 技术栈

- PHP 8.3+
- Laravel 13
- Inertia React 3、React 19、TypeScript
- Tailwind CSS 4、Vite 8
- Laravel Fortify
- Pest、Laravel Pint、ESLint、Prettier

## 初始化

```powershell
composer setup
```

该命令会安装 PHP 和前端依赖、创建 `.env`、生成应用密钥、执行 SQLite
迁移并构建前端资源。

## 本地开发

```powershell
composer dev
```

默认访问地址为 `http://localhost:8000`。

## 验证

```powershell
composer ci:check
```
