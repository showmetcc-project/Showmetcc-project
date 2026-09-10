# Show-Me
Projeto Focado no TCC de Técnico de Informática para Internet

## Figma (Protótipo) do projeto: 
https://www.figma.com/design/OfIRwYhogB1vUhzETBJfBr/Prot%C3%B3tipo-ShowMe?node-id=0-1&t=ab5oVOBfROuqjMkk-1

## Diário de Bordo:
https://docs.google.com/document/d/1eQmMmD_ZRE1Jcs-hp6PKNb1CXcPMcDlCV_TuUi69Axs/edit?usp=sharing

## Execução da aplicação

O ShowMe ainda não possui uma publicação compatível com PHP e MySQL. O antigo link do GitHub Pages exibia apenas arquivos estáticos e não representava a aplicação completa.

Para executar localmente:

1. Importe `assets/banco/showme.sql` no MySQL.
2. Confira a conexão em `config/conexao.php` e crie os arquivos locais `config/email.php` e `config/google.php` a partir dos respectivos arquivos `.example.php`.
3. Execute manualmente as migrações SQL pendentes de `database/migrations/` no banco de desenvolvimento.
4. Execute `composer install`.
5. No VS Code, rode a tarefa **Iniciar servidor PHP do ShowMe** ou use `php -S 127.0.0.1:8000`.

### Configuração do login Google

Crie um **OAuth Client ID** do tipo aplicação Web no Google Cloud/Identity Platform,
cadastre as origens autorizadas usadas pelo projeto e copie o Client ID para
`config/google.php`. O `client_secret` ficou previsto no modelo, mas não é usado pelo
fluxo atual de ID token. Consulte a
[documentação oficial do Google](https://docs.cloud.google.com/identity-platform/docs/web/google?hl=pt-br).

O arquivo `config/google.php` é ignorado pelo Git. O Client ID será exposto no HTML por
definição do fluxo web; já o segredo não deve ser enviado ao navegador nem commitado.

<div align="center">

<img src="./assets/img/showme.png" width="40%" alt="Logotipo ShowMe">

# SHOW ME

### 🌎 Discover culture beyond the capitals.

<img src="https://readme-typing-svg.demolab.com/?font=Orbitron&size=24&duration=3000&pause=1000&color=D9FF00&center=true&vCenter=true&width=700&lines=Eventos+culturais+regionais.;Planejamento+de+viagens.;Rotas+inteligentes.;Hospedagem+e+clima.;Tudo+em+um+s%C3%B3+lugar." />

<br>

![Status](https://img.shields.io/badge/status-em%20desenvolvimento-7B00FF?style=for-the-badge)
![Version](https://img.shields.io/badge/version-1.0-D9FF00?style=for-the-badge)
![License](https://img.shields.io/badge/license-MIT-FF3CAC?style=for-the-badge)

</div>

---

# 🎭 Sobre o Projeto

O **SHOW ME** é uma plataforma inteligente voltada à descoberta de eventos culturais regionais, conectando usuários a experiências além dos grandes centros urbanos.

A proposta integra:

* 📍 geolocalização;
* 🎪 eventos culturais;
* 🛣️ planejamento de trajetos;
* 🌦️ previsão climática;
* 🏨 hospedagem;
* 💰 estimativa de orçamento;

Tudo em uma única experiência moderna e intuitiva.

---

# ✨ Funcionalidades

```txt
📍 Descoberta de eventos próximos
🛣️ Rotas inteligentes
🌦️ Previsão do clima
🏨 Hospedagem integrada
💰 Planejamento de custos
⭐ Recomendações personalizadas
🎭 Eventos regionais e culturais
```

---

# 🖥️ Preview

<div align="center">

<img src="./assets/img/banner.png" width="85%" alt="Banner da aplicação ShowMe">

</div>

---

# 🚀 Tecnologias

<div align="center">

<img src="https://skillicons.dev/icons?i=html,css,js,php,mysql,git,github,vscode" alt="HTML, CSS, JavaScript, PHP, MySQL, Git, GitHub e VS Code" />

</div>

---

# 🎨 Design System

| Cor             | Hex       |
| --------------- | --------- |
| Neon Green      | #D9FF00 |
| Electric Purple | #7B00FF |
| Hot Pink        | #FF3CAC |
| Deep Black      | #050505 |

---

# 🧠 Conceito

> “Mostrar experiências culturais escondidas além das capitais.”

O SHOW ME busca incentivar:

* turismo regional;
* acesso à cultura;
* valorização de cidades menores;
* descoberta de novos eventos.

---

# 📌 Roadmap

* [x] Identidade visual
* [x] Protótipo inicial
* [x] Sistema de eventos
* [ ] Integração com APIs
* [ ] IA de recomendação
* [ ] Aplicativo mobile
* [ ] Sistema de usuários

---

# 📊 Estatísticas

<div align="center">

![GitHub stats](https://github-readme-stats.vercel.app/api?username=Showmetcc-project\&show_icons=true\&theme=tokyonight)

![Top Langs](https://github-readme-stats.vercel.app/api/top-langs/?username=Showmetcc-project\&layout=compact\&theme=tokyonight)

</div>

---

# 🌐 Visão

O projeto pretende transformar a forma como pessoas descobrem cultura, criando uma experiência digital moderna voltada à exploração cultural regional.

---

<div align="center">

### ✨ SHOW ME

Discover. Travel. Experience.

</div>
