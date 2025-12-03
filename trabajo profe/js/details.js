window.onload = () => {
  // ==============================
  // GALERÍA DE IMÁGENES
  // ==============================
  let imgGaleria = document.querySelector("#main-product-img");
  let imgs = document.querySelectorAll(".thumb");

  imgs.forEach((img) => {
    img.addEventListener("click", (evt) => {
      imgGaleria.src = evt.target.src.replace("thumbs/", "");

      imgs.forEach((item) => item.classList.remove("active"));
      evt.target.classList.add("active");
    });
  });

  // ==============================
  // VARIABLES DE PRECIO Y ELEMENTOS
  // ==============================
  const currentPrice = document.querySelector(".current");
  const oldPrice = document.querySelector(".old");
  const discount = document.querySelector(".discount");
  let basePrice = 25; // Por defecto 100ml
  let cantidad = 1;

  // ==============================
  // BOTONES DE TAMAÑO (SIZE)
  // ==============================
  let btns = document.querySelectorAll(".size-btn");
  btns.forEach((btn) => {
    btn.addEventListener("click", (evt) => {
      btns.forEach((item) => item.classList.remove("active"));
      evt.target.classList.add("active");

      // Definir precios según tamaño
      const size = evt.target.textContent.trim();
      if (size === "50ml") basePrice = 15;
      else if (size === "100ml") basePrice = 25;

      calcularTotal();
    });
  });

  // ==============================
  // BOTONES DE CANTIDAD + / -
  // ==============================
  const btnIncrease = document.getElementById("increase");
  const btnDecrease = document.getElementById("decrease");
  const inputCantidad = document.getElementById("quantity");

  btnIncrease.addEventListener("click", () => {
    let val = parseInt(inputCantidad.value) || 1;
    if (val < 15) val++;
    inputCantidad.value = val;
    cantidad = val;
    calcularTotal();
  });

  btnDecrease.addEventListener("click", () => {
    let val = parseInt(inputCantidad.value) || 1;
    if (val > 1) val--;
    inputCantidad.value = val;
    cantidad = val;
    calcularTotal();
  });

  // ==============================
  // VALIDAR INPUT CON ENTER
  // ==============================
  inputCantidad.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      let val = parseInt(inputCantidad.value);
      if (isNaN(val) || val < 1 || val > 15) {
        alert("Por favor, introduce un número entre 1 y 15");
        inputCantidad.value = cantidad;
      } else {
        cantidad = val;
        calcularTotal();
      }
    }
  });

  // ==============================
  // CÁLCULO DE TOTAL Y DESCUENTO
  // ==============================
  function calcularTotal() {
    let descuento = 0;

    if (cantidad > 10) descuento = 0.2;
    else if (cantidad > 5) descuento = 0.1;

    const total = basePrice * cantidad * (1 - descuento);

    currentPrice.textContent = `$${total.toFixed(2)}`;

    // Mostrar precio original (sin descuento)
    oldPrice.textContent = `$${(basePrice * cantidad).toFixed(2)}`;

    // Mostrar el texto del descuento
    discount.textContent =
      descuento > 0 ? `${descuento * 100}% OFF` : "Sin descuento";
  }

  // Calcular precio inicial
  calcularTotal();

  // ==============================
  // SECCIÓN DE COMENTARIOS (LocalStorage)
  // ==============================
  const reviewsContainer = document.getElementById("reviews-container");

  // Crear interfaz de comentarios
  const formHTML = `
    <div class="review">
      <h3>Deja tu comentario</h3>
      <input type="text" id="nombre" placeholder="Tu nombre" style="margin-top:10px; padding:5px; width:200px;">
      <br><br>
      <textarea id="comentario" placeholder="Escribe tu comentario..." style="width:100%; height:60px; padding:5px;"></textarea>
      <br>
      <button id="btn-comentar" class="add-cart" style="margin-top:10px;">Comentar</button>
    </div>
    <div id="lista-comentarios" style="margin-top:20px;"></div>
  `;
  reviewsContainer.innerHTML = formHTML;

  const btnComentar = document.getElementById("btn-comentar");
  const listaComentarios = document.getElementById("lista-comentarios");

  function mostrarComentarios() {
    const comentarios = JSON.parse(localStorage.getItem("comentarios")) || [];
    listaComentarios.innerHTML = "";
    comentarios.forEach((c) => {
      const div = document.createElement("div");
      div.classList.add("review");
      div.innerHTML = `<strong>${c.nombre}:</strong> ${c.comentario}`;
      listaComentarios.appendChild(div);
    });
  }

  btnComentar.addEventListener("click", () => {
    const nombre = document.getElementById("nombre").value.trim();
    const comentario = document.getElementById("comentario").value.trim();

    if (!nombre || !comentario) {
      alert("Por favor llena ambos campos antes de comentar.");
      return;
    }

    const nuevoComentario = { nombre, comentario };
    const comentarios = JSON.parse(localStorage.getItem("comentarios")) || [];
    comentarios.push(nuevoComentario);
    localStorage.setItem("comentarios", JSON.stringify(comentarios));

    document.getElementById("nombre").value = "";
    document.getElementById("comentario").value = "";

    mostrarComentarios();
  });

  mostrarComentarios();

  // ==============================
  // VALORACIÓN ALEATORIA (⭐️)
  // ==============================
  const ratingContainer = document.querySelector(".rating");
  let randomRating = (Math.random() * 4 + 1).toFixed(1); // entre 1 y 5
  let estrellas = "⭐️".repeat(Math.round(randomRating));
  let spanRating = document.createElement("span");
  spanRating.textContent = ` ${estrellas} (${randomRating}/5)`;
  ratingContainer.appendChild(spanRating);
};
