---
name: PokéLab
description: Laboratório Pokémon colorido para explorar e montar uma equipe.
colors:
  primary: "#e94351"
  hero: "#ce3043"
  active-nav: "#b82c3a"
  ink: "#202f3d"
  muted: "#627382"
  paper: "#f7f8fa"
  line: "#dde4e9"
  cyan: "#39c2d1"
  yellow: "#ffdc63"
  console: "#233c50"
typography:
  display:
    fontFamily: "Outfit, Trebuchet MS, sans-serif"
    fontSize: "46px"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.035em"
  heading:
    fontFamily: "Outfit, Trebuchet MS, sans-serif"
    fontSize: "28px"
    fontWeight: 600
    lineHeight: 1.12
  body:
    fontFamily: "Outfit, Trebuchet MS, sans-serif"
    fontSize: "16px"
    lineHeight: 1.5
rounded:
  button: "10px"
  card: "14px"
  panel: "16px"
  hero: "20px"
spacing:
  small: "8px"
  medium: "16px"
  gap: "20px"
  desktop-gutter: "48px"
  mobile-gutter: "20px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "#ffffff"
    rounded: "{rounded.button}"
    padding: "13px 21px"
  button-analysis:
    backgroundColor: "{colors.yellow}"
    textColor: "{colors.ink}"
    rounded: "{rounded.button}"
    padding: "10px 17px"
---

## Overview

**Creative North Star: "Um laboratório para o seu próximo time"**

Identidade de Pokédex tecnológica escolhida pelo usuário: vermelho, painéis azuis, detalhes amarelos e animações. As ilustrações fornecidas são protagonistas. Superfícies claras tornam o catálogo legível e o console azul distingue a equipe em edição.

**Key Characteristics:**
- Vermelho na identidade e na apresentação.
- Azul no console e amarelo nas ações de análise.
- Imagens grandes, órbitas vetoriais e ícones SVG consistentes.
- Captura ao selecionar e varredura ao analisar.

## Colors

A identidade usa primary; hero e active-nav são tons próprios para contraste de texto. Ink e muted hierarquizam texto em paper. Console é a superfície da equipe. As funções têm pares de fundo e texto independentes: atacante coral, defensor verde, versátil lilás, velocista azul e suporte ocre. Cada função também é escrita por extenso.

**The Readable Color Rule.** Cor comunica função, acompanhada de texto; textos pequenos conservam contraste mínimo de 4,5:1.

## Typography

Outfit é hospedada localmente com pesos variáveis. Títulos são compactos e arredondados; texto corrido é normal. O display passa a 37px no celular e 50px em telas grandes. Títulos de seção passam a 25px no celular. Números de identificação e atributos usam algarismos tabulares.

## Layout

Container máximo de 1320px, com gutters desktop de 48px, intermediários de 28px e mobile de 20px. Catálogo em quatro colunas, três até 800px e duas até 640px. Equipe em cinco posições; mobile mantém as cinco em uma faixa horizontal rolável com snap. Detalhes passam de duas para uma coluna até 640px. A apresentação mobile empilha texto e ilustrações.

O catálogo por elementos apresenta os 18 tipos em três colunas, duas até 800px. O montador tem rota própria. Login combina apresentação e formulário em duas colunas, empilhadas até 640px.

## Elevation & Depth

Superfícies planas, bordas finas e camadas tonais. Cards sobem 4px no hover. A arte da apresentação recebe drop-shadow com deslocamento vertical; não aplicar sombra decorativa a todos os painéis.

## Shapes

Retângulos de cantos arredondados sustentam painéis, botões e cards. Círculos representam Pokébolas, órbitas e evolução. A marca usa um quadrado vermelho rotacionado com Pokébola SVG. Ícones compartilham traço arredondado.

## Components

Botões escuros executam ações gerais; amarelo identifica a análise no console. Campos brancos têm borda line e foco azul visível. Filtros usam fundo escuro quando ativos e aria-pressed. Cards separam ilustração em fundo tonal de nome e botão de adicionar em branco. Espaços vazios da equipe têm contorno tracejado; preenchidos ganham imagem, nome, função, troca e remoção. A navegação ativa usa texto vermelho escuro e sublinhado.

Tiles de elemento usam os 18 pares existentes `--type-bg`/`--type-ink`, com nome, contagem e ilustração; o seletor horizontal reutiliza esses pares. Login mantém rótulos visíveis, campos brancos e controle de senha. O controle de animações no cabeçalho expõe `aria-pressed` e persiste a preferência; `html.motion-off` desativa animações, transições e rolagem suave.

O perfil oferece galeria pesquisável com 54 Pokémon em radios nativos: borda, fundo e marca de seleção identificam o avatar ativo, com foco visível, prévia imediata e confirmação ao salvar. O menu de perfil usa disclosure nativo `details`/`summary`. Formulários de perfil e recuperação mantêm rótulos visíveis, dicas e mensagens de sucesso ou erro. A extensão preserva o `html.motion-off` global e a responsividade existente; a galeria reduz de seis para cinco e quatro colunas, e o perfil empilha no celular.

**The Motion With Meaning Rule.** Animar seleção, varredura e resposta dos controles. A preferência reduced motion desativa movimento e rolagem suave. Conteúdo permanece acessível sem aguardar animação.

## Do's and Don'ts

### Do:
- Do usar as ilustrações locais e nomes reais do catálogo.
- Do manter função escrita junto da sua cor.
- Do preservar foco visível, estados vazios e movimento reduzido.

### Don't:
- Don't usar as cores claras das ilustrações como cor de texto corrido.
- Don't esconder controles da equipe no celular.
- Don't transformar a análise educativa em promessa de vitória.
