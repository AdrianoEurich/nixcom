<h2 class="h3 mb-4">Cadastrar Novo Item</h2>
<p>Preencha os detalhes do novo item:</p>
<form>
    <div class="mb-3">
        <label for="exampleInputEmail1" class="form-label">Email address</label>
        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
    </div>
    <div class="mb-3">
        <label for="exampleInputPassword1" class="form-label">Password</label>
        <input type="password" class="form-control" id="exampleInputPassword1">
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="exampleCheck1">
        <label class="form-check-label" for="exampleCheck1">Check me out</label>
    </div>
    <!-- Botão inicialmente habilitado, sem spinner -->
    <button id="btnSalvar" class="btn btn-primary" type="button"
        onclick="salvarDados()">
        <span id="btnText">Salvar</span>
        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"
            role="status" aria-hidden="true"></span>
    </button>
</form>