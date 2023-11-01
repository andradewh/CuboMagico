document.addEventListener('DOMContentLoaded', function() {
    // Aguarde o documento estar pronto
    var formEdicaoUsuario = document.querySelector('#formEdicaoUsuario');

    if (formEdicaoUsuario) {
        // Certifique-se de que o formulário foi encontrado
        formEdicaoUsuario.addEventListener('submit', function(e) {
            // Evite o envio do formulário padrão (caso deseje executar alguma ação personalizada)
            e.preventDefault();

            // Você pode adicionar sua lógica aqui para manipular o formulário
        });
    }
});
