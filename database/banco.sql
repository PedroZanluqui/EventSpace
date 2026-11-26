CREATE DATABASE IF NOT EXISTS eventspace;
USE eventspace;

DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS espacos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('cliente','administrador') NOT NULL DEFAULT 'cliente'
);

CREATE TABLE espacos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  descricao TEXT,
  comodidades VARCHAR(255),
  capacidade INT NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  disponivel TINYINT(1) NOT NULL DEFAULT 1,
  responsavel_nome VARCHAR(100) DEFAULT 'Coordenação EventSpace',
  responsavel_contato VARCHAR(100) DEFAULT 'contato@eventspace.com.br · (11) 99999-0000'
);

CREATE TABLE reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  espaco_id INT NOT NULL,
  cliente_id INT NOT NULL,
  data_evento DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  tipo_evento VARCHAR(60) NOT NULL,
  qtd_pessoas INT NOT NULL,
  observacoes TEXT,
  status ENUM('PENDENTE','APROVADA','REJEITADA','CANCELADA','CONCLUIDA') NOT NULL DEFAULT 'PENDENTE',
  data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao DATETIME NULL DEFAULT NULL,
  notificado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (espaco_id) REFERENCES espacos(id),
  FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
);

CREATE TABLE favoritos (
  usuario_id INT NOT NULL,
  espaco_id INT NOT NULL,
  PRIMARY KEY (usuario_id, espaco_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (espaco_id) REFERENCES espacos(id) ON DELETE CASCADE
);

INSERT INTO usuarios (nome, email, senha, tipo) VALUES
  ('João Silva', 'joao@email.com', '$2y$10$fFY/iziXBZaTlqA5z/rEbOKURlpRZjTW0VRJPbtM87ysAnYDytD/a', 'cliente'),
  ('Administrador', 'admin@eventspace.com', '$2y$10$/gkXirVXJRj.zizEAGbIPuPifYsdUFvs.F42TJ2x6KIDgPE4tHelO', 'administrador');

INSERT INTO espacos (nome, tipo, descricao, comodidades, capacidade, preco, responsavel_nome, responsavel_contato) VALUES
  ('Espaço Premium', 'Salão de festas', 'Salão climatizado com pista de dança, iluminação cênica e cozinha industrial de apoio.', 'Wi-Fi, Ar-condicionado, Estacionamento, Cozinha, Som', 150, 2500.00, 'Mariana Costa', 'mariana@eventspace.com.br · (11) 98888-1111'),
  ('Grande Salão Imperial', 'Auditório', 'Auditório amplo com palco elevado, telão de alta definição e acústica tratada para congressos e palestras.', 'Wi-Fi, Projetor, Som, Ar-condicionado, Acessibilidade', 300, 4000.00, 'Roberto Almeida', 'roberto@eventspace.com.br · (11) 98888-2222'),
  ('Jardim Eventos', 'Área externa', 'Área verde arborizada com pergolado coberto, ideal para casamentos ao ar livre e confraternizações.', 'Estacionamento, Iluminação, Banheiros, Cozinha, Área externa', 200, 3000.00, 'Camila Souza', 'camila@eventspace.com.br · (11) 98888-3333'),
  ('Sala Executiva Central', 'Sala de reunião', 'Sala corporativa equipada com mesa de conferência, tela interativa e isolamento acústico para treinamentos.', 'Wi-Fi, Projetor, Ar-condicionado, Café e Água', 30, 600.00, 'Carlos Mendes', 'carlos@eventspace.com.br · (11) 98888-4444');

INSERT INTO reservas (espaco_id, cliente_id, data_evento, hora_inicio, hora_fim, tipo_evento, qtd_pessoas, status, data_atualizacao, notificado) VALUES
  (1, 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY), '14:00', '22:00', 'Aniversário', 80, 'PENDENTE', NULL, 1),
  (2, 1, DATE_ADD(CURDATE(), INTERVAL 18 DAY), '09:00', '18:00', 'Workshop Corporativo', 120, 'APROVADA', NOW(), 0);
