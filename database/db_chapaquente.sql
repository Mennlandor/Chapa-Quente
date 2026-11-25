CREATE TABLE `categoria` (
  `idCategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nomeCategoria` varchar(100) NOT NULL,
  PRIMARY KEY (`idCategoria`),
  UNIQUE KEY `nomeCategoria` (`nomeCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cliente` (
  `idCliente` int(11) NOT NULL AUTO_INCREMENT,
  `nomeCliente` varchar(100) NOT NULL,
  `emailCliente` varchar(100) NOT NULL,
  `telefoneCliente` varchar(12) DEFAULT NULL,
  `senhaCliente` varchar(255) NOT NULL,
  PRIMARY KEY (`idCliente`),
  UNIQUE KEY `emailCliente` (`emailCliente`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pedido` (
  `idPedido` int(11) NOT NULL AUTO_INCREMENT,
  `dataPedido` datetime NOT NULL,
  `statusPedido` enum('Preparando','Concluido','Cancelado') DEFAULT 'Preparando',
  `totalPedido` decimal(5,2) NOT NULL,
  `clientePedido_FK` int(11) NOT NULL,
  PRIMARY KEY (`idPedido`),
  KEY `clientePedido_FK` (`clientePedido_FK`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`clientePedido_FK`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `produto` (
  `idProduto` int(11) NOT NULL AUTO_INCREMENT,
  `nomeProduto` varchar(100) NOT NULL,
  `descricaoProduto` text NOT NULL,
  `precoProduto` decimal(5,2) NOT NULL,
  `statusProduto` enum('Disponivel','Indisponivel') DEFAULT 'Disponivel',
  `caminhoImagemProduto` varchar(500) NOT NULL,
  `categoriaProduto_FK` int(11) NOT NULL,
  PRIMARY KEY (`idProduto`),
  KEY `categoriaProduto_FK` (`categoriaProduto_FK`),
  CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoriaProduto_FK`) REFERENCES `categoria` (`idCategoria`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pagamento` (
  `idPagamento` int(11) NOT NULL AUTO_INCREMENT,
  `metodoPagamento` enum('PIX','Debito','Credito','Em dinheiro') DEFAULT 'PIX',
  `valorPagamento` decimal(5,2) NOT NULL,
  `pedidoPagamento_FK` int(11) NOT NULL,
  PRIMARY KEY (`idPagamento`),
  KEY `pedidoPagamento_FK` (`pedidoPagamento_FK`),
  CONSTRAINT `fk_pagamento_pedido` FOREIGN KEY (`pedidoPagamento_FK`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `carrinho_cliente` (
  `cliente_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`cliente_id`,`produto_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `carrinho_cliente_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`idCliente`) ON DELETE CASCADE,
  CONSTRAINT `carrinho_cliente_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `produto_pedido` (
  `idProduto_Pedido` int(11) NOT NULL AUTO_INCREMENT,
  `quantidadeProduto_pedido` int(11) NOT NULL,
  `precoUnitarioProduto_pedido` decimal(5,2) NOT NULL,
  `pedidoProduto_pedido_FK` int(11) NOT NULL,
  `produtoProduto_pedido_FK` int(11) NOT NULL,
  PRIMARY KEY (`idProduto_Pedido`),
  KEY `pedidoProduto_pedido_FK` (`pedidoProduto_pedido_FK`),
  KEY `produtoProduto_pedido_FK` (`produtoProduto_pedido_FK`),
  CONSTRAINT `fk_produto_pedido_pedido` FOREIGN KEY (`pedidoProduto_pedido_FK`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_produto_pedido_produto` FOREIGN KEY (`produtoProduto_pedido_FK`) REFERENCES `produto` (`idProduto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
