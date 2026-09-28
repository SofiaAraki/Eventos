# 🎓 Sistema de Gestão de Eventos Acadêmicos — FAFRAM

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Framework](https://img.shields.io/badge/Framework-Adianti-2496ED?style=for-the-badge)](https://www.adianti.com.br/)
[![Database](https://img.shields.io/badge/Database-MySQL_5.7%2B_%7C_MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](#)

O **Sistema de Gestão de Eventos Acadêmicos** é uma plataforma robusta e integrada desenvolvida para a **FAFRAM (Faculdade Dr. Francisco Maeda)**. O sistema automatiza todo o ciclo de vida de eventos acadêmicos, seminários, simpósios e defesas de Trabalho de Conclusão de Curso (TCC), abrangendo desde a proposição e aprovação até o credenciamento presencial via **QR Code**, gestão financeira de inscrições e emissão automatizada de certificados verificáveis com assinatura digital.

---

## 📋 Sumário

- [Visão Geral e Arquitetura](#-visão-geral-e-arquitetura)
- [🚀 Tecnologias e Bibliotecas](#-tecnologias-e-bibliotecas)
- [🏗️ Perfis de Acesso e Funcionalidades](#️-perfis-de-acesso-e-funcionalidades)
- [📁 Estrutura de Diretórios](#-estrutura-de-diretórios)
- [📊 Modelo de Dados (ER)](#-modelo-de-dados-er)
- [⚙️ Pré-requisitos do Sistema](#️-pré-requisitos-do-sistema)
- [🛠️ Passo a Passo de Instalação](#️-passo-a-passo-de-instalação)
- [💡 Fluxos de Destaque](#-fluxos-de-destaque)
- [🧩 Roteiro de Expansão (Roadmap)](#-roteiro-de-expansão-roadmap)
- [👥 Créditos e Suporte](#-créditos-e-suporte)

---

## 🔍 Visão Geral e Arquitetura

A plataforma foi projetada utilizando a arquitetura **MVC (Model-View-Controller)** com o **Adianti Framework**, aproveitando o padrão **Active Record** para persistência e **Bootstrap 5** para layouts responsivos e acessíveis. 

O foco principal do sistema é a automação acadêmica: eliminar filas em check-in, calcular o tempo de permanência de participantes em tempo real e emitir certificados autênticos via chave/token único auditável.

---

## 🚀 Tecnologias e Bibliotecas

| Componente | Tecnologia | Função no Projeto |
| :--- | :--- | :--- |
| **Linguagem Base** | **PHP 8.4+** | Processamento backend, regras de negócio e tipagem forte. |
| **Framework Web** | **Adianti Framework** | Estrutura RAD (Rapid Application Development) para CRUDs e gestão de permissões. |
| **Banco de Dados** | **MySQL 5.7+ / MariaDB** | Armazenamento relacional com suporte a transações ACID. |
| **Gerenciador de Pacotes**| **Composer** | Gestão de dependências e autoloading PSR-4. |
| **Credenciamento / QR** | **BaconQrCode** | Geração dinâmica de QR Codes para crachás, ingressos e validação. |
| **Geração de Documentos** | **DomPDF / FPDF** | Renderização de relatórios gerenciais e certificados em PDF. |
| **Disparo de E-mails** | **PHPMailer** | Notificações de inscrição, confirmação de pagamento e envio de chaves. |
| **Interface / UI** | **Bootstrap 5 (AdminBS5)**| Layout responsivo com tabelas dinâmicas, modais e componentes interativos. |

---

## 🏗️ Perfis de Acesso e Funcionalidades

O sistema opera sob um controle de acesso baseado em papéis (RBAC - Role-Based Access Control):

```
                                 ┌────────────────────────┐
                                 │   Administrador /      │
                                 │       Gerente          │
                                 └───────────┬────────────┘
                                             │
             ┌───────────────────────────────┼───────────────────────────────┐
             │                               │                               │
┌────────────▼────────────┐     ┌────────────▼────────────┐     ┌────────────▼────────────┐
│      Coordenador        │     │         Monitor         │     │       Participante      │
└─────────────────────────┘     └─────────────────────────┘     └─────────────────────────┘
```

### 1. 🎓 Participante / Aluno
- **Catálogo de Eventos:** Consulta de eventos abertos com filtros por área, data e carga horária.
- **Inscrições & Pagamentos:** Inscrição simplificada com acompanhamento do status (Pendente / Confirmado).
- **Área do Aluno:** Visualização de crachá virtual com QR Code individual para credenciamento.
- **Emissão de Certificados:** Download direto do PDF assim que atingida a presença mínima configurada.

### 2. 👨‍🏫 Coordenador
- **Proposição de Eventos:** Submissão de propostas de novos eventos para validação gerencial.
- **Validador Público:** Consulta de autenticidade de certificados por código/token de validação.
- **Gestão Didática:** Acompanhamento de turmas e inscritos vinculados aos seus cursos.

### 3. 📱 Monitor / Credenciador
- **Scanner QR Code:** Interface otimizada para câmeras de dispositivos móveis para check-in e check-out instantâneo.
- **Painel de Frequência:** Monitoramento em tempo real do fluxo de participantes no recinto.
- **Relatório de Movimentação:** Registro detalhado de horário de entrada e saída por aluno.

### 4. ⚙️ Gerente / Administração
- **Gestão Global de Eventos:** Aprovação, edição, encerramento e controle financeiro/pagamentos.
- **Motor de Certificados:** Configuração do modelo visual (background, logotipo, assinaturas), carga horária total e % de presença mínima exigida.
- **Módulo de TCC Integrado:** Criação automática de eventos para defesas de TCC, associando alunos, orientadores e banca examinadora.
- **Auditoria e Logs:** Registro histórico de todas as operações realizadas na plataforma.

---

## 📁 Estrutura de Diretórios

```
Eventos/
├── index.php                 # Ponto de entrada principal da aplicação
├── engine.php                # Núcleo de execução do framework
├── init.php                  # Bootstrapper e inicialização de sessões/constantes
├── composer.json             # Gerenciador de dependências PHP
├── menu-*.xml                # Definição de menus por perfil de usuário
├── app/
│   ├── config/               # Arquivos de conexão com banco de dados (ex: teste.php, permission.php)
│   ├── control/              # Controllers da aplicação (Visualizações, Formulários e Telas)
│   │   ├── EventoForm.class.php
│   │   ├── RelatorioEventosView.class.php
│   │   └── ...
│   ├── model/                # Classes de Modelo Active Record (Evento, Inscricao, Presenca, etc.)
│   ├── view/                 # Templates visuais e componentes gráficos
│   ├── database/             # Scripts de criação e população SQL (eventos.sql)
│   └── output/               # Diretório temporário para PDFs e relatórios gerados
├── database/                 # Backup de migrações e conexões secundárias
├── lib/                      # Framework Adianti e bibliotecas de terceiros
├── resources/                # Assets estáticos (CSS, JS, Imagens, Logos e Fontes)
└── tmp/                      # Cache e arquivos temporários do servidor
```

---

## 📊 Modelo de Dados (ER)

A estrutura do banco de dados assegura alta consistência e rastreabilidade:

```
┌──────────────────┐       1:N       ┌──────────────────┐       1:N       ┌──────────────────┐
│     evento       ├─────────────────►    inscricao     ├─────────────────►     presenca     │
└────────┬─────────┘                 └────────┬─────────┘                 └──────────────────┘
         │                                    │
         │ 1:N                                │ 1:1
         ▼                                    ▼
┌──────────────────┐                 ┌──────────────────┐
│   certificado    │                 │     registro     │
└──────────────────┘                 └──────────────────┘
         │                                (Emissão)
         │ 1:N
┌────────┴─────────┐
│       tcc        │ (Composição: autor, banca, orientador)
└──────────────────┘
```

### Principais Entidades

- **`evento`**: Armazena título, descrição, local, data/hora, carga horária, limite de vagas e gerente responsável.
- **`inscricao`**: Vincula o usuário ao evento, registrando a data de inscrição, tipo de participação (Aluno, Autor, Ouvinte, Banca) e status financeiro.
- **`presenca`**: Guarda os marcadores de entrada (`check-in`) e saída (`check-out`), calculando o tempo exato de permanência em minutos/horas.
- **`certificado`**: Armazena os parâmetros visuais e regras (ex: % mínimo de presença exigido para liberação).
- **`registro`**: Armazena o histórico final do certificado emitido, contendo a chave hash/token único e data de emissão.
- **`pagamentos`**: Registra transações, valores, comprovantes e status de pagamento das inscrições.
- **`tcc` / `autor` / `banca`**: Estrutura complementar para gestão acadêmica e emissão automática de certificados de defesas de TCC.

---

## ⚙️ Pré-requisitos do Sistema

Certifique-se de que o servidor atende aos seguintes pré-requisitos antes da instalação:

- **Servidor Web:** Apache 2.4+ (com `mod_rewrite` habilitado) ou Nginx.
- **PHP:** Versão 8.4 ou superior.
- **Extensões PHP Obrigatórias:**
  - `php-mysql`
  - `php-gd` (para manipulação de imagens e geração de QR Codes)
  - `php-mbstring`
  - `php-xml` / `php-dom`
  - `php-curl`
  - `php-zip`
- **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.3+.
- **Composer:** Instalado e configurado globalmente.

---

## 🛠️ Passo a Passo de Instalação

### 1. Clonar o Repositório
```bash
git clone https://github.com/SofiaAraki/Eventos.git
cd Eventos
```

### 2. Instalar Dependências via Composer
```bash
composer install
```

### 3. Configurar o Banco de Dados
1. Crie um banco de dados relacional chamado `eventos` em seu SGDB:
   ```sql
   CREATE DATABASE eventos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Importe o script SQL com a estrutura inicial localizado em `app/database/eventos.sql`.
3. Edite as credenciais de acesso aos arquivos de configuração do banco:
   - `app/config/teste.php`
   - `app/config/permission.php`

Exemplo de configuração (`app/config/teste.php`):
```php
return [
    'host' => '127.0.0.1',
    'name' => 'eventos',
    'user' => 'seu_usuario',
    'pass' => 'sua_senha',
    'type' => 'mysql',
    'port' => '3306'
];
```

### 4. Ajustar Permissões de Pastas
Conceda permissão de escrita para o usuário do servidor web (ex: `www-data`) nas seguintes pastas:
```bash
chmod -R 775 tmp/ app/output/ app/database/
```

### 5. Acessar a Aplicação
Aponte o VirtualHost do seu servidor web para o diretório raiz do projeto.
- **URL por Padrão:** `http://localhost/Eventos`
- **Credenciais Iniciais (Adianti):**
  - **Usuário:** `admin`
  - **Senha:** `admin`

---

## 💡 Fluxos de Destaque

### 🔄 Check-in Inteligente
 Ao escanear o QR Code de um aluno no credenciamento, o sistema detecta se existe um registro em aberto. Se não houver, registra a **entrada**; se houver, registra a **saída** e calcula instantaneamente a permanência líquida.

### 📜 Liberação Automática de Certificados
O sistema soma as horas computadas do aluno através da tabela de `presenca`. Se o total for maior ou igual ao percentual estipulado no cadastro do `certificado`, o botão de download é liberado automaticamente no painel do participante.

### 🛡️ Autenticidade e Anti-Fraude
Cada certificado gerado contém uma chave Hash SHA-256 única impressa no documento acompanhada de um QR Code apontando para o validador público da FAFRAM, permitindo a verificação de autenticidade por qualquer instituição terceirizada.

### 🎓 Integração com Defesas de TCC
Ao cadastrar um TCC no módulo acadêmico, a plataforma cria automaticamente o evento associado e gera as inscrições e certificados específicos para o autor, orientador e membros da banca examinadora.

---

## 🧩 Roteiro de Expansão (Roadmap)

- [ ] **Autenticação Institucional (OAuth2/Single Sign-On):** Permitir login via Google Workspace Institucional.
- [ ] **Integração de Pagamentos Online:** Conexão nativa com PIX e Cartão de Crédito via API Mercado Pago / Asaas.
- [ ] **Assinatura Digital ICP-Brasil:** Validação de certificados com certificados digitais A1/A3.
- [ ] **Dashboard Analítico:** Gráficos interativos (Chart.js) para análise de taxa de adesão, arrecadação e taxa de presença por curso.
- [ ] **Upload de Trabalhos de TCC:** Módulo para submissão e download dos arquivos finais das monografias em PDF.

---

## 👥 Créditos e Suporte

Desenvolvido para a **FAFRAM — Faculdade Dr. Francisco Maeda**.

<<<<<<< HEAD
Mantido pela equipe de desenvolvimento e colaboradores do repositório. Para suporte ou reporte de bugs, abra uma *Issue* no repositório oficial.
=======
Mantido pela equipe de desenvolvimento e colaboradores do repositório. Para suporte ou reporte de bugs, abra uma *Issue* no repositório oficial.
>>>>>>> 9f6e3a508e852607139571164852f5e0f9b40714
