<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241227112621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE archivo (id INT AUTO_INCREMENT NOT NULL, estado_id INT DEFAULT NULL, autor_id INT NOT NULL, nombre VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, tipo VARCHAR(50) NOT NULL, creado DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', acceso_ver SMALLINT NOT NULL, accesos INT NOT NULL, INDEX IDX_3529B4829F5A440B (estado_id), INDEX IDX_3529B48214D45BBE (autor_id), INDEX idx_ruta_nombre (ruta, nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cirhus_archivo (id INT AUTO_INCREMENT NOT NULL, incidencia_id INT NOT NULL, archivo_id INT DEFAULT NULL, fecha DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_770A50CFE1605BE2 (incidencia_id), UNIQUE INDEX UNIQ_770A50CF46EBF93B (archivo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cirhus_incidencia (id INT AUTO_INCREMENT NOT NULL, solicitante_id INT NOT NULL, descripcion LONGTEXT NOT NULL, INDEX IDX_27F12BC7C680A87 (solicitante_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cirhus_incidencia_archivo (incidencia_id INT NOT NULL, archivo_id INT NOT NULL, INDEX IDX_51974F1FE1605BE2 (incidencia_id), INDEX IDX_51974F1F46EBF93B (archivo_id), PRIMARY KEY(incidencia_id, archivo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cirhus_incidencia_apunte (id INT AUTO_INCREMENT NOT NULL, servicio_id INT DEFAULT NULL, incidencia_id INT DEFAULT NULL, estado_id INT DEFAULT NULL, autor_id INT DEFAULT NULL, empleado_id INT DEFAULT NULL, comentario LONGTEXT DEFAULT NULL, fecha_inicio DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', fecha_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_36C85F2B71CAA3E7 (servicio_id), INDEX IDX_36C85F2BE1605BE2 (incidencia_id), INDEX IDX_36C85F2B9F5A440B (estado_id), INDEX IDX_36C85F2B14D45BBE (autor_id), INDEX IDX_36C85F2B952BE730 (empleado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cirhus_servicio (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(255) NOT NULL, nombre VARCHAR(255) NOT NULL, correo LONGTEXT DEFAULT NULL, telefono VARCHAR(255) NOT NULL, responsable VARCHAR(255) NOT NULL, INDEX idx_codigo (codigo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cuestiona_cuestionario (id INT AUTO_INCREMENT NOT NULL, estado_id INT DEFAULT NULL, autor_id INT DEFAULT NULL, codigo VARCHAR(100) NOT NULL, titulo VARCHAR(255) NOT NULL, descripcion LONGTEXT NOT NULL, bienvenida LONGTEXT DEFAULT NULL, despedida LONGTEXT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, editable TINYINT(1) DEFAULT 1 NOT NULL, privado TINYINT(1) DEFAULT 1 NOT NULL, fecha_alta DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', fecha_baja DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', configuracion JSON DEFAULT NULL, INDEX IDX_2435EE999F5A440B (estado_id), INDEX IDX_2435EE9914D45BBE (autor_id), INDEX index_cuestionario_codigo (codigo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cuestiona_formulario (id INT AUTO_INCREMENT NOT NULL, cuestionario_id INT DEFAULT NULL, usuario_id INT DEFAULT NULL, fecha_grabacion DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', fecha_envio DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_551278FB8AAA3CFB (cuestionario_id), INDEX IDX_551278FBDB38439E (usuario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cuestiona_grupo (id INT AUTO_INCREMENT NOT NULL, cuestionario_id INT NOT NULL, activa TINYINT(1) NOT NULL, orden SMALLINT NOT NULL, codigo VARCHAR(100) NOT NULL, titulo VARCHAR(255) NOT NULL, descripcion LONGTEXT NOT NULL, INDEX IDX_15D3A8968AAA3CFB (cuestionario_id), INDEX idx_grupo_cuestionario_orden (cuestionario_id, orden), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cuestiona_pregunta (id INT AUTO_INCREMENT NOT NULL, grupo_id INT NOT NULL, activa TINYINT(1) NOT NULL, opcional TINYINT(1) NOT NULL, orden SMALLINT NOT NULL, codigo VARCHAR(100) NOT NULL, titulo VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, ayuda LONGTEXT DEFAULT NULL, tipo SMALLINT NOT NULL, opciones JSON DEFAULT NULL, reducida TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_D4B8651C9C833003 (grupo_id), INDEX idx_pregunta_grupo_orden (grupo_id, orden), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cuestiona_respuesta (id INT AUTO_INCREMENT NOT NULL, formulario_id INT NOT NULL, pregunta_id INT NOT NULL, valor JSON NOT NULL, INDEX IDX_5BCCA69A41CFE234 (formulario_id), INDEX IDX_5BCCA69A31A5801E (pregunta_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desempenyo_evalua (id INT AUTO_INCREMENT NOT NULL, empleado_id INT NOT NULL, evaluador_id INT DEFAULT NULL, cuestionario_id INT NOT NULL, formulario_id INT DEFAULT NULL, corrector_id INT DEFAULT NULL, tipo_evaluador SMALLINT DEFAULT 1 NOT NULL, rechazado DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', rechazo_texto LONGTEXT DEFAULT NULL, registrado DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', correccion DOUBLE PRECISION DEFAULT NULL, comentario LONGTEXT DEFAULT NULL, corregido DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', habilita TINYINT(1) DEFAULT 0 NOT NULL, origen VARCHAR(10) NOT NULL, INDEX IDX_122A1E0F952BE730 (empleado_id), INDEX IDX_122A1E0F40815979 (evaluador_id), INDEX IDX_122A1E0F8AAA3CFB (cuestionario_id), INDEX IDX_122A1E0F41CFE234 (formulario_id), INDEX IDX_122A1E0F3A6E8746 (corrector_id), INDEX desempenyo_cuestionario_empleado_evaluador_idx (cuestionario_id, empleado_id, evaluador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desempenyo_incidencia (id INT AUTO_INCREMENT NOT NULL, incidencia_id INT DEFAULT NULL, tipo_id INT DEFAULT NULL, cuestionario_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_CC9CD7FAE1605BE2 (incidencia_id), INDEX IDX_CC9CD7FAA9276E6C (tipo_id), INDEX IDX_CC9CD7FA8AAA3CFB (cuestionario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE desempenyo_tipo_incidencia (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(50) NOT NULL, descripcion LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE estado (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(50) NOT NULL, descripcion LONGTEXT NOT NULL, icono VARCHAR(50) DEFAULT NULL, color VARCHAR(22) DEFAULT NULL, tipo VARCHAR(10) NOT NULL, INDEX idx_estado_nombre (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_ausencia (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(50) NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_empleado (id INT AUTO_INCREMENT NOT NULL, usuario_id INT DEFAULT NULL, situacion_id INT DEFAULT NULL, grupo_id INT DEFAULT NULL, unidad_id INT DEFAULT NULL, ausencia_id INT DEFAULT NULL, validador_id INT DEFAULT NULL, nrp VARCHAR(50) NOT NULL, nivel SMALLINT DEFAULT NULL, vigente DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', cesado DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', consolidado SMALLINT DEFAULT NULL, consolidacion DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', antiguedad SMALLINT DEFAULT NULL, en_titular SMALLINT DEFAULT NULL, en_ocupada SMALLINT DEFAULT NULL, nombre VARCHAR(100) NOT NULL, apellido1 VARCHAR(100) NOT NULL, apellido2 VARCHAR(100) DEFAULT NULL, doc_identidad VARCHAR(11) DEFAULT NULL, UNIQUE INDEX UNIQ_52EC6D96DB38439E (usuario_id), INDEX IDX_52EC6D9696714AEF (situacion_id), INDEX IDX_52EC6D969C833003 (grupo_id), INDEX IDX_52EC6D969D01464C (unidad_id), INDEX IDX_52EC6D9660C93433 (ausencia_id), INDEX IDX_52EC6D96B3B24877 (validador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_grupo (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(50) NOT NULL, adscripcion VARCHAR(1) NOT NULL, nivel_minimo SMALLINT DEFAULT NULL, nivel_maximo SMALLINT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_situacion (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(50) NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_unidad (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(50) NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(180) NOT NULL, password VARCHAR(100) NOT NULL, roles JSON NOT NULL, correo VARCHAR(100) DEFAULT NULL, creado DATETIME NOT NULL, modificado DATETIME NOT NULL, UNIQUE INDEX UNIQ_2265B05DAA08CB10 (login), INDEX idx_usuario_login (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE archivo ADD CONSTRAINT FK_3529B4829F5A440B FOREIGN KEY (estado_id) REFERENCES estado (id)');
        $this->addSql('ALTER TABLE archivo ADD CONSTRAINT FK_3529B48214D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE cirhus_archivo ADD CONSTRAINT FK_770A50CFE1605BE2 FOREIGN KEY (incidencia_id) REFERENCES cirhus_incidencia (id)');
        $this->addSql('ALTER TABLE cirhus_archivo ADD CONSTRAINT FK_770A50CF46EBF93B FOREIGN KEY (archivo_id) REFERENCES archivo (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia ADD CONSTRAINT FK_27F12BC7C680A87 FOREIGN KEY (solicitante_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia_archivo ADD CONSTRAINT FK_51974F1FE1605BE2 FOREIGN KEY (incidencia_id) REFERENCES cirhus_incidencia (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cirhus_incidencia_archivo ADD CONSTRAINT FK_51974F1F46EBF93B FOREIGN KEY (archivo_id) REFERENCES archivo (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte ADD CONSTRAINT FK_36C85F2B71CAA3E7 FOREIGN KEY (servicio_id) REFERENCES cirhus_servicio (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte ADD CONSTRAINT FK_36C85F2BE1605BE2 FOREIGN KEY (incidencia_id) REFERENCES cirhus_incidencia (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte ADD CONSTRAINT FK_36C85F2B9F5A440B FOREIGN KEY (estado_id) REFERENCES estado (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte ADD CONSTRAINT FK_36C85F2B14D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte ADD CONSTRAINT FK_36C85F2B952BE730 FOREIGN KEY (empleado_id) REFERENCES plantilla_empleado (id)');
        $this->addSql('ALTER TABLE cuestiona_cuestionario ADD CONSTRAINT FK_2435EE999F5A440B FOREIGN KEY (estado_id) REFERENCES estado (id)');
        $this->addSql('ALTER TABLE cuestiona_cuestionario ADD CONSTRAINT FK_2435EE9914D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE cuestiona_formulario ADD CONSTRAINT FK_551278FB8AAA3CFB FOREIGN KEY (cuestionario_id) REFERENCES cuestiona_cuestionario (id)');
        $this->addSql('ALTER TABLE cuestiona_formulario ADD CONSTRAINT FK_551278FBDB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE cuestiona_grupo ADD CONSTRAINT FK_15D3A8968AAA3CFB FOREIGN KEY (cuestionario_id) REFERENCES cuestiona_cuestionario (id)');
        $this->addSql('ALTER TABLE cuestiona_pregunta ADD CONSTRAINT FK_D4B8651C9C833003 FOREIGN KEY (grupo_id) REFERENCES cuestiona_grupo (id)');
        $this->addSql('ALTER TABLE cuestiona_respuesta ADD CONSTRAINT FK_5BCCA69A41CFE234 FOREIGN KEY (formulario_id) REFERENCES cuestiona_formulario (id)');
        $this->addSql('ALTER TABLE cuestiona_respuesta ADD CONSTRAINT FK_5BCCA69A31A5801E FOREIGN KEY (pregunta_id) REFERENCES cuestiona_pregunta (id)');
        $this->addSql('ALTER TABLE desempenyo_evalua ADD CONSTRAINT FK_122A1E0F952BE730 FOREIGN KEY (empleado_id) REFERENCES plantilla_empleado (id)');
        $this->addSql('ALTER TABLE desempenyo_evalua ADD CONSTRAINT FK_122A1E0F40815979 FOREIGN KEY (evaluador_id) REFERENCES plantilla_empleado (id)');
        $this->addSql('ALTER TABLE desempenyo_evalua ADD CONSTRAINT FK_122A1E0F8AAA3CFB FOREIGN KEY (cuestionario_id) REFERENCES cuestiona_cuestionario (id)');
        $this->addSql('ALTER TABLE desempenyo_evalua ADD CONSTRAINT FK_122A1E0F41CFE234 FOREIGN KEY (formulario_id) REFERENCES cuestiona_formulario (id)');
        $this->addSql('ALTER TABLE desempenyo_evalua ADD CONSTRAINT FK_122A1E0F3A6E8746 FOREIGN KEY (corrector_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE desempenyo_incidencia ADD CONSTRAINT FK_CC9CD7FAE1605BE2 FOREIGN KEY (incidencia_id) REFERENCES cirhus_incidencia (id)');
        $this->addSql('ALTER TABLE desempenyo_incidencia ADD CONSTRAINT FK_CC9CD7FAA9276E6C FOREIGN KEY (tipo_id) REFERENCES desempenyo_tipo_incidencia (id)');
        $this->addSql('ALTER TABLE desempenyo_incidencia ADD CONSTRAINT FK_CC9CD7FA8AAA3CFB FOREIGN KEY (cuestionario_id) REFERENCES cuestiona_cuestionario (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D96DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D9696714AEF FOREIGN KEY (situacion_id) REFERENCES plantilla_situacion (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D969C833003 FOREIGN KEY (grupo_id) REFERENCES plantilla_grupo (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D969D01464C FOREIGN KEY (unidad_id) REFERENCES plantilla_unidad (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D9660C93433 FOREIGN KEY (ausencia_id) REFERENCES plantilla_ausencia (id)');
        $this->addSql('ALTER TABLE plantilla_empleado ADD CONSTRAINT FK_52EC6D96B3B24877 FOREIGN KEY (validador_id) REFERENCES plantilla_empleado (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE archivo DROP FOREIGN KEY FK_3529B4829F5A440B');
        $this->addSql('ALTER TABLE archivo DROP FOREIGN KEY FK_3529B48214D45BBE');
        $this->addSql('ALTER TABLE cirhus_archivo DROP FOREIGN KEY FK_770A50CFE1605BE2');
        $this->addSql('ALTER TABLE cirhus_archivo DROP FOREIGN KEY FK_770A50CF46EBF93B');
        $this->addSql('ALTER TABLE cirhus_incidencia DROP FOREIGN KEY FK_27F12BC7C680A87');
        $this->addSql('ALTER TABLE cirhus_incidencia_archivo DROP FOREIGN KEY FK_51974F1FE1605BE2');
        $this->addSql('ALTER TABLE cirhus_incidencia_archivo DROP FOREIGN KEY FK_51974F1F46EBF93B');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte DROP FOREIGN KEY FK_36C85F2B71CAA3E7');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte DROP FOREIGN KEY FK_36C85F2BE1605BE2');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte DROP FOREIGN KEY FK_36C85F2B9F5A440B');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte DROP FOREIGN KEY FK_36C85F2B14D45BBE');
        $this->addSql('ALTER TABLE cirhus_incidencia_apunte DROP FOREIGN KEY FK_36C85F2B952BE730');
        $this->addSql('ALTER TABLE cuestiona_cuestionario DROP FOREIGN KEY FK_2435EE999F5A440B');
        $this->addSql('ALTER TABLE cuestiona_cuestionario DROP FOREIGN KEY FK_2435EE9914D45BBE');
        $this->addSql('ALTER TABLE cuestiona_formulario DROP FOREIGN KEY FK_551278FB8AAA3CFB');
        $this->addSql('ALTER TABLE cuestiona_formulario DROP FOREIGN KEY FK_551278FBDB38439E');
        $this->addSql('ALTER TABLE cuestiona_grupo DROP FOREIGN KEY FK_15D3A8968AAA3CFB');
        $this->addSql('ALTER TABLE cuestiona_pregunta DROP FOREIGN KEY FK_D4B8651C9C833003');
        $this->addSql('ALTER TABLE cuestiona_respuesta DROP FOREIGN KEY FK_5BCCA69A41CFE234');
        $this->addSql('ALTER TABLE cuestiona_respuesta DROP FOREIGN KEY FK_5BCCA69A31A5801E');
        $this->addSql('ALTER TABLE desempenyo_evalua DROP FOREIGN KEY FK_122A1E0F952BE730');
        $this->addSql('ALTER TABLE desempenyo_evalua DROP FOREIGN KEY FK_122A1E0F40815979');
        $this->addSql('ALTER TABLE desempenyo_evalua DROP FOREIGN KEY FK_122A1E0F8AAA3CFB');
        $this->addSql('ALTER TABLE desempenyo_evalua DROP FOREIGN KEY FK_122A1E0F41CFE234');
        $this->addSql('ALTER TABLE desempenyo_evalua DROP FOREIGN KEY FK_122A1E0F3A6E8746');
        $this->addSql('ALTER TABLE desempenyo_incidencia DROP FOREIGN KEY FK_CC9CD7FAE1605BE2');
        $this->addSql('ALTER TABLE desempenyo_incidencia DROP FOREIGN KEY FK_CC9CD7FAA9276E6C');
        $this->addSql('ALTER TABLE desempenyo_incidencia DROP FOREIGN KEY FK_CC9CD7FA8AAA3CFB');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D96DB38439E');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D9696714AEF');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D969C833003');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D969D01464C');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D9660C93433');
        $this->addSql('ALTER TABLE plantilla_empleado DROP FOREIGN KEY FK_52EC6D96B3B24877');
        $this->addSql('DROP TABLE archivo');
        $this->addSql('DROP TABLE cirhus_archivo');
        $this->addSql('DROP TABLE cirhus_incidencia');
        $this->addSql('DROP TABLE cirhus_incidencia_archivo');
        $this->addSql('DROP TABLE cirhus_incidencia_apunte');
        $this->addSql('DROP TABLE cirhus_servicio');
        $this->addSql('DROP TABLE cuestiona_cuestionario');
        $this->addSql('DROP TABLE cuestiona_formulario');
        $this->addSql('DROP TABLE cuestiona_grupo');
        $this->addSql('DROP TABLE cuestiona_pregunta');
        $this->addSql('DROP TABLE cuestiona_respuesta');
        $this->addSql('DROP TABLE desempenyo_evalua');
        $this->addSql('DROP TABLE desempenyo_incidencia');
        $this->addSql('DROP TABLE desempenyo_tipo_incidencia');
        $this->addSql('DROP TABLE estado');
        $this->addSql('DROP TABLE plantilla_ausencia');
        $this->addSql('DROP TABLE plantilla_empleado');
        $this->addSql('DROP TABLE plantilla_grupo');
        $this->addSql('DROP TABLE plantilla_situacion');
        $this->addSql('DROP TABLE plantilla_unidad');
        $this->addSql('DROP TABLE usuario');
    }
}
