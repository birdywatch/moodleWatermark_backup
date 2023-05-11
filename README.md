Instalação:

1.  Colocar conteudos do repositório no diretorio /var/www/moodle/mod/moodlewatermark

    git clone https://github.com/birdywatch/moodleWatermark_backup.git /var/www/moodle/mod/moodlewatermark

2. Instalar depedencias

    2.1. TCPDF/FPDI
        2.1.1 No diretório moodlewatermark, correr o comando:
            composer require setasign/tcpdf fpdi

    2.2 ImageImagick
        2.2.1 Em sistemas Debian, correr o comando:
            sudo apt-get install imagemagick

        2.2.2 Editar o ficheiro policy.xml para permitir edição de PDFs pelo ImageImagick:
            //Utilizar editor de texto para abrir o ficheiro
                nano /etc/ImageImagick-6/policy.xml

            //Substituir a linha
                <policy domain="coder" rights="none" pattern="PDF" />
            //Pela linha
                <policy domain="coder" rights="read | write" pattern="PDF" />
            //Guardar o ficheiro

3. Atualizar o Moodle, ao abrir o cliente web, uma atualização para instalar o módulo deve aparecer, e depois dessa atualização o módulo deve estar funcional!