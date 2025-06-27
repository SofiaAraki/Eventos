# 🎓 Sistema de Gerenciamento de Eventos Acadêmicos

Este sistema web permite o gerenciamento completo de eventos acadêmicos, incluindo controle de inscrições, emissão de certificados, pagamentos e organização de TCCs.

## 🚀 Funcionalidades Principais

- **Cadastro e Gerenciamento de Eventos**
- **Inscrição de Participantes** com controle de status (Pendente/Confirmado)
- **Emissão de Certificados** com templates personalizados
- **Controle de Pagamentos**
- **Gerenciamento de TCCs** com autores, orientadores e banca avaliadora
- **Painéis e Relatórios Dinâmicos**

## 📁 Estrutura do Projeto

```
Eventos/
├── index.php                  # Entrada principal
├── composer.json              # Gerenciador de dependências
├── engine.php, init.php       # Inicialização e núcleo da aplicação
├── menu-*.xml                 # Menus dinâmicos
├── app/
│   ├── control/               # Controllers (ex: RelatorioEventosView.class.php)
│   ├── model/                 # Modelos (ex: Eventos, Inscricoes)
│   └── view/                  # Views (formulários e listagens)
├── database/                  # Scripts SQL e conexões
├── lib/                       # Bibliotecas externas
├── resources/                 # Templates, imagens e assets
└── ...
```

## 🗃️ Estrutura de Dados (Banco de Dados)

O sistema é composto por tabelas principais como:

- `eventos`: cadastro de eventos
- `inscricoes`: registros de participação
- `certificados`: emissão de certificados
- `pagamentos`: status e histórico de pagamentos
- `tccs`, `autores`, `banca`: gerenciamento de TCCs e membros envolvidos

## ✅ Tecnologias Utilizadas

- **PHP** (com padrão MVC leve)
- **Adianti Framework**
- **Bootstrap (Datagrid Wrapper)**
- **MySQL**

## ⚙️ Requisitos

- PHP 7.4+
- MySQL 5.7+
- Composer
- Servidor HTTP (Apache ou Nginx)

## ▶️ Como Executar

1. Clone o repositório ou extraia o conteúdo.
2. Configure o banco de dados com os scripts SQL disponíveis.
3. Ajuste as configurações em `engine.php` e `init.php`.
4. Acesse `index.php` via navegador.

## 🧩 Sugestões de Expansão

- Autenticação com OAuth (ex: login com Google)
- Emissão de certificados com assinatura digital
- Dashboard analítico com gráficos (ex: Chart.js)
- Upload e gerenciamento de arquivos de TCC


