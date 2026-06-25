// Troca de abas: Favoritos <-> Planejados
document.querySelectorAll('.aba').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        // Atualiza botões
        document.querySelectorAll('.aba').forEach(b => b.classList.remove('ativa'));
        btn.classList.add('ativa');

        // Atualiza seções
        document.querySelectorAll('.conteudo').forEach(s => s.classList.remove('ativa'));
        document.getElementById(tab).classList.add('ativa');
    });
});