# 🛑 PARAGEM OBRIGATÓRIA - LER ISTO PARA FUNCIONAR

O site está feito em **PHP**. O Windows não consegue correr PHP sozinho. Precisas do **XAMPP**.

Se estás a ver código em vez do site, é porque a pasta está no sítio errado.

## 🛠️ COMO RESOLVER (Passo a Passo)

1.  **Encontrar a pasta do XAMPP**:
    *   Vai a `Este PC` -> `Disco Local (C:)` -> `xampp` -> `htdocs`.
    *   (Normalmente é `C:\xampp\htdocs`).

2.  **Mover o Site**:
    *   Pega nesta pasta onde estás (`Site pap`).
    *   **COPIA** e **COLA** dentro da pasta `htdocs`.

3.  **Ligar o Servidor**:
    *   Abre o **XAMPP Control Panel**.
    *   Clica em **Start** no **Apache**.
    *   Clica em **Start** no **MySQL**.

4.  **Abrir o Site**:
    *   Dá dois cliques no ficheiro `ABRIR_SITE.bat` (que eu criei agora).
    *   OU abre o browser e escreve: `http://localhost/Site pap/`

## ⚠️ Base de Dados (Para o Login funcionar)
1.  Vai a `http://localhost/phpmyadmin`
2.  Cria uma base de dados chamada `lookup_db` (se não existir).
3.  Clica em "Importar" e escolhe o ficheiro `database.sql` desta pasta.

---
**Se não fizeres isto, o site NUNCA vai funcionar.**
