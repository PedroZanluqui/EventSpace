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
  responsavel_contato VARCHAR(100) DEFAULT 'contato@eventspace.com.br · (11) 99999-0000',
  imagem_url VARCHAR(500) NULL DEFAULT NULL,
  imagem_credito VARCHAR(255) NULL DEFAULT NULL,
  galeria_imagens TEXT NULL DEFAULT NULL,
  endereco VARCHAR(255) NULL DEFAULT NULL,
  cidade VARCHAR(100) NOT NULL DEFAULT 'São Paulo',
  estado VARCHAR(2) NOT NULL DEFAULT 'SP',
  cep VARCHAR(15) NULL DEFAULT NULL,
  latitude DECIMAL(10,7) NULL DEFAULT NULL,
  longitude DECIMAL(10,7) NULL DEFAULT NULL
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
  motivo_recusa TEXT NULL DEFAULT NULL,
  prazo_resolucao DATETIME NULL DEFAULT NULL,
  cancelada_automaticamente TINYINT(1) NOT NULL DEFAULT 0,
  motivo_cancelamento TEXT NULL DEFAULT NULL,
  reenviada TINYINT(1) NOT NULL DEFAULT 0,
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

INSERT INTO espacos (
  nome, tipo, descricao, comodidades, capacidade, preco,
  responsavel_nome, responsavel_contato,
  endereco, cidade, estado, cep, latitude, longitude
) VALUES
  (
    'Espaço Premium',
    'Salão de festas',
    'Salão climatizado com pista de dança, iluminação cênica e cozinha industrial de apoio.',
    'Wi-Fi, Ar-condicionado, Estacionamento, Cozinha, Som',
    150,
    2500.00,
    'Mariana Costa',
    'mariana@eventspace.com.br · (11) 98888-1111',
    'Av. Paulista, 1578 - Bela Vista',
    'São Paulo',
    'SP',
    '01310-200',
    -23.5614140,
    -46.6558819
  ),
  (
    'Grande Salão Imperial',
    'Auditório',
    'Auditório amplo com palco elevado, telão de alta definição e acústica tratada para congressos e palestras.',
    'Wi-Fi, Projetor, Som, Ar-condicionado, Acessibilidade',
    300,
    4000.00,
    'Roberto Almeida',
    'roberto@eventspace.com.br · (11) 98888-2222',
    'Av. Brigadeiro Faria Lima, 3477 - Itaim Bibi',
    'São Paulo',
    'SP',
    '04538-133',
    -23.5868430,
    -46.6820340
  ),
  (
    'Jardim Eventos',
    'Área externa',
    'Área verde arborizada com pergolado coberto, ideal para casamentos ao ar livre e confraternizações.',
    'Estacionamento, Iluminação, Banheiros, Cozinha, Área externa',
    200,
    3000.00,
    'Camila Souza',
    'camila@eventspace.com.br · (11) 98888-3333',
    'Av. Pedro Álvares Cabral, s/n - Vila Mariana',
    'São Paulo',
    'SP',
    '04094-050',
    -23.5874162,
    -46.6576336
  ),
  (
    'Sala Executiva Central',
    'Sala de reunião',
    'Sala corporativa equipada com mesa de conferência, tela interativa e isolamento acústico para treinamentos.',
    'Wi-Fi, Projetor, Ar-condicionado, Café e Água',
    30,
    600.00,
    'Carlos Mendes',
    'carlos@eventspace.com.br · (11) 98888-4444',
    'Rua Augusta, 1939 - Cerqueira César',
    'São Paulo',
    'SP',
    '01413-000',
    -23.5600580,
    -46.6626140
  );

INSERT INTO reservas (
  espaco_id, cliente_id, data_evento, hora_inicio, hora_fim, tipo_evento,
  qtd_pessoas, observacoes, status, data_atualizacao, notificado,
  motivo_recusa, prazo_resolucao, cancelada_automaticamente, motivo_cancelamento, reenviada
) VALUES
  (
    1, 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY), '14:00', '22:00', 'Aniversário',
    80, NULL, 'PENDENTE', NULL, 1,
    NULL, NULL, 0, NULL, 0
  ),
  (
    2, 1, DATE_ADD(CURDATE(), INTERVAL 18 DAY), '09:00', '18:00', 'Workshop Corporativo',
    120, NULL, 'APROVADA', NOW(), 0,
    NULL, NULL, 0, NULL, 0
  ),
  (
    3, 1, DATE_ADD(CURDATE(), INTERVAL 25 DAY), '16:00', '23:30', 'Confraternização',
    110, 'Necessário uso de som até o encerramento.', 'REJEITADA', NOW(), 0,
    'Por favor, confirme nas observações se haverá montagem prévia de som e ajuste o horário de término para até 21h.',
    DATE_ADD(NOW(), INTERVAL 2 DAY), 0, NULL, 0
  );
