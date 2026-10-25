/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: mariadb
-- ------------------------------------------------------
-- Server version	10.4.34-MariaDB-1:10.4.34+maria~ubu2004

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `artista`
--

DROP TABLE IF EXISTS `artista`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artista` (
  `id_artista` int(11) NOT NULL AUTO_INCREMENT,
  `nome_artista` varchar(150) NOT NULL,
  `genero_artista` varchar(100) DEFAULT NULL,
  `imagem_artista` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_artista`),
  UNIQUE KEY `uk_artista_nome` (`nome_artista`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `artista_evento`
--

DROP TABLE IF EXISTS `artista_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artista_evento` (
  `id_artista` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  PRIMARY KEY (`id_artista`,`id_evento`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `artista_evento_ibfk_1` FOREIGN KEY (`id_artista`) REFERENCES `artista` (`id_artista`) ON DELETE CASCADE,
  CONSTRAINT `artista_evento_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comunidade_curtida`
--

DROP TABLE IF EXISTS `comunidade_curtida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidade_curtida` (
  `id_post` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_post`,`id_usuario`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `comunidade_curtida_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `comunidade_post` (`id_post`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_curtida_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comunidade_denuncia`
--

DROP TABLE IF EXISTS `comunidade_denuncia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidade_denuncia` (
  `id_denuncia` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) DEFAULT NULL,
  `id_resposta` int(11) DEFAULT NULL,
  `id_midia` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `motivo` varchar(100) NOT NULL,
  `status_denuncia` enum('pendente','mantido','removido') NOT NULL DEFAULT 'pendente',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_moderacao` datetime DEFAULT NULL,
  `id_admin_moderacao` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_denuncia`),
  UNIQUE KEY `uk_denuncia_post_usuario` (`id_post`,`id_usuario`),
  UNIQUE KEY `uk_denuncia_resposta_usuario` (`id_resposta`,`id_usuario`),
  UNIQUE KEY `uk_denuncia_midia_usuario` (`id_midia`,`id_usuario`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_admin_moderacao` (`id_admin_moderacao`),
  CONSTRAINT `comunidade_denuncia_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `comunidade_post` (`id_post`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_denuncia_ibfk_2` FOREIGN KEY (`id_resposta`) REFERENCES `comunidade_resposta` (`id_resposta`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_denuncia_ibfk_3` FOREIGN KEY (`id_midia`) REFERENCES `comunidade_midia` (`id_midia`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_denuncia_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_denuncia_ibfk_5` FOREIGN KEY (`id_admin_moderacao`) REFERENCES `usuario` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `chk_comunidade_denuncia_alvo` CHECK (`id_post` is not null and `id_resposta` is null and `id_midia` is null or `id_post` is null and `id_resposta` is not null and `id_midia` is null or `id_post` is null and `id_resposta` is null and `id_midia` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comunidade_midia`
--

DROP TABLE IF EXISTS `comunidade_midia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidade_midia` (
  `id_midia` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `legenda` varchar(255) DEFAULT NULL,
  `permitir_download` tinyint(1) NOT NULL DEFAULT 0,
  `status_midia` enum('ativo','removido') NOT NULL DEFAULT 'ativo',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_midia`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_comunidade_midia_evento_data` (`id_evento`,`data_criacao`),
  CONSTRAINT `comunidade_midia_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_midia_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comunidade_post`
--

DROP TABLE IF EXISTS `comunidade_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidade_post` (
  `id_post` int(11) NOT NULL AUTO_INCREMENT,
  `id_evento` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `categoria` enum('Duvida','Dica','Transporte','Hospedagem','Companhia','Relato') NOT NULL,
  `texto` text NOT NULL,
  `status_post` enum('ativo','removido') NOT NULL DEFAULT 'ativo',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_post`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_comunidade_post_evento_data` (`id_evento`,`data_criacao`),
  CONSTRAINT `comunidade_post_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_post_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comunidade_resposta`
--

DROP TABLE IF EXISTS `comunidade_resposta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidade_resposta` (
  `id_resposta` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `texto` text NOT NULL,
  `status_resposta` enum('ativo','removido') NOT NULL DEFAULT 'ativo',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_resposta`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_comunidade_resposta_post_data` (`id_post`,`data_criacao`),
  CONSTRAINT `comunidade_resposta_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `comunidade_post` (`id_post`) ON DELETE CASCADE,
  CONSTRAINT `comunidade_resposta_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `evento`
--

DROP TABLE IF EXISTS `evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evento` (
  `id_evento` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitacao_origem` int(11) DEFAULT NULL,
  `num_evento` int(11) DEFAULT NULL,
  `nome_evento` varchar(100) NOT NULL,
  `cep_evento` char(8) NOT NULL,
  `endereco_evento` varchar(255) NOT NULL,
  `numero_endereco` varchar(20) DEFAULT NULL,
  `rua_evento` varchar(100) DEFAULT NULL,
  `cidade_evento` varchar(100) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `descricao_evento` varchar(1000) NOT NULL,
  `data_evento` date NOT NULL,
  `horario_evento` time NOT NULL,
  `gratuidade` tinyint(1) NOT NULL DEFAULT 0,
  `valor_ingresso_minimo` decimal(10,2) DEFAULT NULL,
  `valor_ingresso_maximo` decimal(10,2) DEFAULT NULL,
  `categoria_evento` varchar(255) NOT NULL,
  `classificacao_indicativa` varchar(20) NOT NULL DEFAULT 'Livre',
  `link_oficial` varchar(255) NOT NULL,
  `imagem_evento` varchar(255) NOT NULL,
  `alt_imagem` varchar(255) DEFAULT NULL,
  `status_evento` enum('ativo','cancelado') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id_evento`),
  UNIQUE KEY `id_solicitacao_origem` (`id_solicitacao_origem`),
  KEY `idx_evento_data` (`data_evento`),
  CONSTRAINT `fk_evento_solicitacao` FOREIGN KEY (`id_solicitacao_origem`) REFERENCES `solicitacao` (`id_solicitacao`) ON DELETE SET NULL,
  CONSTRAINT `chk_evento_valores_ingresso` CHECK (`gratuidade` = 1 and `valor_ingresso_minimo` is null and `valor_ingresso_maximo` is null or `gratuidade` = 0 and `valor_ingresso_minimo` > 0 and (`valor_ingresso_maximo` is null or `valor_ingresso_maximo` >= `valor_ingresso_minimo`))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `favoritos`
--

DROP TABLE IF EXISTS `favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favoritos` (
  `id_favorito` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  PRIMARY KEY (`id_favorito`),
  UNIQUE KEY `uk_favorito` (`id_user`,`id_evento`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `google_calendar_token`
--

DROP TABLE IF EXISTS `google_calendar_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_calendar_token` (
  `id_usuario` int(11) NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text NOT NULL,
  `expira_em` datetime NOT NULL,
  `data_conexao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `google_calendar_token_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preferencias`
--

DROP TABLE IF EXISTS `preferencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `preferencias` (
  `id_preferencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `genero_preferido` varchar(100) NOT NULL,
  PRIMARY KEY (`id_preferencia`),
  UNIQUE KEY `uk_preferencia` (`id_user`,`genero_preferido`),
  CONSTRAINT `preferencias_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rota`
--

DROP TABLE IF EXISTS `rota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rota` (
  `id_rota` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `meio_transporte` varchar(30) DEFAULT NULL,
  `distancia_km` decimal(10,2) DEFAULT NULL,
  `tempo_estimado` int(11) DEFAULT NULL,
  `origem` varchar(255) DEFAULT NULL,
  `orcamento_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custo_ingresso` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custo_transporte` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hospedagem_necessaria` tinyint(1) NOT NULL DEFAULT 0,
  `nome_hospedagem` varchar(100) DEFAULT NULL,
  `custo_hospedagem` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_rota`),
  UNIQUE KEY `uk_rota` (`id_user`,`id_evento`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `rota_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `rota_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitacao`
--

DROP TABLE IF EXISTS `solicitacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacao` (
  `id_solicitacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nome_evento` varchar(100) NOT NULL,
  `status_solicitacao` enum('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  `foto` varchar(255) DEFAULT NULL,
  `alt_imagem` varchar(255) DEFAULT NULL,
  `horario_evento` time DEFAULT NULL,
  `data_evento` date NOT NULL,
  `cep_evento` char(8) DEFAULT NULL,
  `endereco_evento` varchar(255) DEFAULT NULL,
  `numero_endereco` varchar(20) DEFAULT NULL,
  `rua_evento` varchar(100) DEFAULT NULL,
  `cidade_evento` varchar(100) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `categoria_evento` varchar(255) NOT NULL,
  `classificacao_indicativa` varchar(20) NOT NULL DEFAULT 'Livre',
  `link_oficial` varchar(255) NOT NULL,
  `gratuidade` tinyint(1) DEFAULT NULL,
  `valor_ingresso_minimo` decimal(10,2) DEFAULT NULL,
  `valor_ingresso_maximo` decimal(10,2) DEFAULT NULL,
  `descricao_evento` varchar(1000) DEFAULT NULL,
  `descricao_artista` varchar(1000) DEFAULT NULL,
  `nome_artista_solicitado` varchar(150) DEFAULT NULL,
  `genero_artista` varchar(100) DEFAULT NULL,
  `data_solicitacao` datetime NOT NULL DEFAULT current_timestamp(),
  `ticketmaster_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_solicitacao`),
  UNIQUE KEY `uk_solicitacao_ticketmaster` (`ticketmaster_id`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `solicitacao_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `chk_solicitacao_valores_ingresso` CHECK (`gratuidade` is null and `valor_ingresso_minimo` is null and `valor_ingresso_maximo` is null or `gratuidade` = 1 and `valor_ingresso_minimo` is null and `valor_ingresso_maximo` is null or `gratuidade` = 0 and `valor_ingresso_minimo` > 0 and (`valor_ingresso_maximo` is null or `valor_ingresso_maximo` >= `valor_ingresso_minimo`))
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nome_user` varchar(100) NOT NULL,
  `sobrenome` varchar(100) DEFAULT NULL,
  `email_user` varchar(100) NOT NULL,
  `senha_user` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `foto_banner` varchar(255) DEFAULT NULL,
  `tipo_usuario` enum('comum','admin') NOT NULL DEFAULT 'comum',
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_usuario_email` (`email_user`),
  UNIQUE KEY `uk_usuario_google` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-25 17:59:17
