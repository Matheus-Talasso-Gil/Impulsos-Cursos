-- impede favoritos duplicados e vincula cada favorito a uma conta e um curso
CREATE TABLE IF NOT EXISTS favoritos (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    curso_id INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT favoritos_usuario_fk FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT favoritos_curso_fk FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    CONSTRAINT favoritos_usuario_curso_unique UNIQUE (usuario_id, curso_id)
);
