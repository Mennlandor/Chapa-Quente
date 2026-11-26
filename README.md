<div align="center">
  <img src="assets/readme/capa.svg" alt="Chapa Quente — capa ilustrada com um hambúrguer" width="960">


  <p>
    <a href="#recursos">Recursos</a> ·
    <a href="#rodar-no-xampp">Rodar no XAMPP</a> ·
    <a href="#estrutura-do-projeto">Estrutura do projeto</a>
  </p>

  <img src="assets/readme/tecnologias.svg" alt="PHP · MariaDB ou MySQL · HTML, CSS e JavaScript · Chart.js" width="700">
</div>

## Visão geral


![Fluxo do pedido: o cliente escolhe no cardápio, revisa o carrinho e confirma no checkout. A loja acompanha o pedido no painel.](assets/readme/fluxo.svg)

## Recursos

| Área do cliente | Painel administrativo |
| --- | --- |
| Cadastro e login de clientes | Resumo de vendas, ganho médio e pedidos em preparo |
| Cardápio com busca por nome e filtro por categoria | Gráfico de vendas dos últimos sete dias e ranking de pratos |
| Detalhes do prato, quantidade e carrinho | Busca de pedidos por ID ou cliente e filtro por estado |
| Checkout com Pix, crédito, débito ou dinheiro | Conclusão ou cancelamento de pedidos em preparo |
| Confirmação do pedido | Cadastro de categorias e pratos com imagem opcional |

O checkout registra a forma de pagamento escolhida junto ao pedido. **Nenhum valor é cobrado.** O painel administrativo foi feito para demonstração local e não exige autenticação administrativa.

## Rodar no XAMPP

Você precisa do **XAMPP** com Apache, PHP e MySQL/MariaDB. Os passos abaixo assumem uma instalação em `C:\xampp` e a pasta do projeto com o nome `foodstock`.

1. Clone o repositório para a pasta `htdocs`:

   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/Mennlandor/Chapa-Quente.git foodstock
   ```

2. Inicie **Apache** e **MySQL** pelo painel do XAMPP.
3. Abra o [phpMyAdmin local](http://localhost/phpmyadmin/), crie o banco `db_chapaquente` e selecione-o. Use a aba **Importar** para carregar [database/db_chapaquente.sql](database/db_chapaquente.sql) em um banco vazio.
4. Se sua conexão usa outro usuário, senha ou porta, ajuste os parâmetros em [bd.php](bd.php).

| Tela | Endereço local |
| --- | --- |
| Cadastro e login | [localhost/foodstock/](http://localhost/foodstock/) |
| Cardápio | [localhost/foodstock/page/menu.php](http://localhost/foodstock/page/menu.php) |
| Painel administrativo | [localhost/foodstock/admin/](http://localhost/foodstock/admin/) |

Para testar o fluxo completo com um banco novo, abra o **Cardápio** do painel administrativo e cadastre uma categoria e um prato. Crie uma conta de teste na loja, entre, adicione o prato ao carrinho e confirme o pedido. Volte ao painel para encontrá-lo em **Pedidos**.

## Estrutura do projeto

| Arquivo ou pasta | Responsabilidade |
| --- | --- |
| [index.html](index.html) | Tela inicial com os formulários de cadastro e login |
| [page/](page/) | Cardápio, detalhes de produto, carrinho e checkout |
| [action/](action/) | Cadastro, login, logout e gravação de pedidos |
| [admin/](admin/) | Painel, consultas de indicadores e formulários de gestão |
| [assets/](assets/) | Logo, ícones, imagens de produtos e ilustrações do README |
| [database/db_chapaquente.sql](database/db_chapaquente.sql) | Estrutura das tabelas e seus relacionamentos |
| [bd.php](bd.php) | Conexão PHP com o banco via MySQLi |

O PHP gera as páginas e grava os dados no banco. O JavaScript atualiza interações como o total por quantidade na página do produto e os filtros da tabela de pedidos. Na confirmação, uma transação grava **pedido, itens e forma de pagamento** em conjunto.
