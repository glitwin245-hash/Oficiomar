// =========================================================
// OFICIO MAR – JAVASCRIPT MODERNO & INTERACTIVO
// =========================================================

// --- VARIABLES GLOBALES DE FILTRADO ---
var filtroCategoriaActivo = 'todos';
var searchDebounceTimer = null;

// --- BÚSQUEDA Y FILTRADO DE PROFESIONALES ---
function buscarCards() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(function() {
    aplicarFiltrosGlobales();
  }, 180);
}

// Alias para compatibilidad
function buscar() {
  buscarCards();
}

function filtrarCards(tipo, btn) {
  filtroCategoriaActivo = tipo;

  // Actualizar clase activa en chips
  document.querySelectorAll('.chip').forEach(function(c) {
    c.classList.remove('active');
  });
  if (btn) {
    btn.classList.add('active');
  }

  aplicarFiltrosGlobales();
}

// Alias para compatibilidad
function filtrar(tipo, btn) {
  filtrarCards(tipo, btn);
}

function aplicarFiltrosGlobales() {
  var input = document.getElementById('searchInput');
  var query = input ? input.value.trim().toLowerCase() : '';
  var cards = document.querySelectorAll('#grid .card');
  var visibles = 0;

  cards.forEach(function(card) {
    var rubro = (card.dataset.rubro || '').toLowerCase();
    var nombre = (card.dataset.nombre || '').toLowerCase();
    var zona = (card.dataset.zona || '').toLowerCase();
    var disponible = card.dataset.disponible === '1';

    var pasaCategoria = true;
    if (filtroCategoriaActivo === 'disponible') {
      pasaCategoria = disponible;
    } else if (filtroCategoriaActivo !== 'todos') {
      pasaCategoria = (rubro === filtroCategoriaActivo.toLowerCase());
    }

    var pasaBusqueda = true;
    if (query.length > 0) {
      pasaBusqueda = (
        nombre.includes(query) ||
        rubro.includes(query) ||
        zona.includes(query)
      );
    }

    var visible = pasaCategoria && pasaBusqueda;
    if (visible) {
      card.classList.remove('oculta');
      card.style.animation = 'fadeIn 0.25s ease';
      visibles++;
    } else {
      card.classList.add('oculta');
    }
  });

  // Contador de resultados y empty state
  var label = document.getElementById('label');
  if (label) {
    if (visibles === 0) {
      label.textContent = 'No se encontraron profesionales';
    } else {
      label.textContent = visibles + ' profesional' + (visibles !== 1 ? 'es' : '') + ' disponible' + (visibles !== 1 ? 's' : '') + ' en Mar del Plata';
    }
  }

  var emptyState = document.getElementById('sinResultados');
  if (emptyState) {
    emptyState.style.display = (visibles === 0) ? 'block' : 'none';
  }
}

// --- MODO URGENCIA / EMERGENCIA ---
function activarModoUrgencia() {
  var chipDisp = document.querySelector('.chip[data-tipo="disponible"]');
  if (chipDisp) {
    filtrarCards('disponible', chipDisp);
  } else {
    // Buscar el chip que tenga texto relacionado a disponibles
    var chips = document.querySelectorAll('.chip');
    chips.forEach(function(c) {
      if (c.textContent.toLowerCase().includes('disponible')) {
        filtrarCards('disponible', c);
      }
    });
  }
  mostrarToast('🚨 Mostrando profesionales disponibles de inmediato en Mar del Plata', 'ok');
}

// --- MODAL DE CONTACTO RÁPIDO PARA TURISTAS ---
function abrirModalContacto(profesionalId, nombre, rubro, telefono, sitioWeb) {
  var modal = document.getElementById('modalContacto');
  if (!modal) return;

  // Llenar datos
  var nombreEl = document.getElementById('modalProfNombre');
  var rubroEl = document.getElementById('modalProfRubro');
  var idInput = document.getElementById('modalProfIdInput');
  var btnLlamar = document.getElementById('modalBtnLlamar');
  var btnWsp = document.getElementById('modalBtnWsp');

  if (nombreEl) nombreEl.textContent = nombre;
  if (rubroEl) rubroEl.textContent = rubro;
  if (idInput) idInput.value = profesionalId;

  // Configurar botones telefónicos
  if (telefono && telefono.trim() !== '') {
    var telLimpio = telefono.replace(/[^0-9]/g, '');
    if (btnLlamar) {
      btnLlamar.href = 'tel:' + telLimpio;
      btnLlamar.style.display = 'inline-flex';
    }
    if (btnWsp) {
      var wspNumero = telLimpio;
      // Ajuste para números de Argentina si no tienen prefijo
      if (wspNumero.length === 10) wspNumero = '549' + wspNumero;
      var msg = encodeURIComponent('¡Hola ' + nombre + '! Te contacto a través de OficioMar para consultar por un trabajo de ' + rubro + '.');
      btnWsp.href = 'https://wa.me/' + wspNumero + '?text=' + msg;
      btnWsp.style.display = 'inline-flex';
    }
  } else {
    if (btnLlamar) btnLlamar.style.display = 'none';
    if (btnWsp) btnWsp.style.display = 'none';
  }

  modal.classList.add('open');
}

function cerrarModalContacto() {
  var modal = document.getElementById('modalContacto');
  if (modal) modal.classList.remove('open');
}

// Cerrar modal al cliquear el fondo backdrop
window.addEventListener('click', function(e) {
  var modal = document.getElementById('modalContacto');
  if (modal && e.target === modal) {
    cerrarModalContacto();
  }
});

// --- SISTEMA DE RESEÑAS CON ESTRELLAS INTERACTIVAS ---
function inicializarStarRating() {
  var ratingContainer = document.querySelector('.star-rating-box');
  if (!ratingContainer) return;

  var stars = ratingContainer.querySelectorAll('.star');
  var inputHidden = document.getElementById('inputCalificacion');
  var descText = document.getElementById('starRatingDesc');

  var descripciones = {
    1: '1 - Muy insatisfecho 😞',
    2: '2 - Regular / Regular 😐',
    3: '3 - Bueno / Aceptable 🙂',
    4: '4 - Muy bueno / Recomendable 😊',
    5: '5 - ¡Excelente servicio! 🌟'
  };

  stars.forEach(function(star) {
    star.addEventListener('mouseenter', function() {
      var val = parseInt(this.dataset.val);
      stars.forEach(function(s) {
        var sVal = parseInt(s.dataset.val);
        s.classList.toggle('hover', sVal <= val);
      });
      if (descText) descText.textContent = descripciones[val] || '';
    });

    star.addEventListener('mouseleave', function() {
      var actualVal = inputHidden ? parseInt(inputHidden.value || 0) : 0;
      stars.forEach(function(s) {
        var sVal = parseInt(s.dataset.val);
        s.classList.toggle('hover', false);
        s.classList.toggle('selected', sVal <= actualVal);
      });
      if (descText) descText.textContent = actualVal ? (descripciones[actualVal] || '') : 'Seleccioná tu calificación';
    });

    star.addEventListener('click', function() {
      var val = parseInt(this.dataset.val);
      if (inputHidden) inputHidden.value = val;
      stars.forEach(function(s) {
        var sVal = parseInt(s.dataset.val);
        s.classList.toggle('selected', sVal <= val);
      });
      if (descText) descText.textContent = descripciones[val];
    });
  });
}

// --- PESTAÑAS (TABS) DEL DASHBOARD PROFESIONAL ---
function cambiarTab(tabId, btn) {
  // Desactivar todos los botones y contenidos
  document.querySelectorAll('.tab-btn').forEach(function(b) {
    b.classList.remove('active');
  });
  document.querySelectorAll('.tab-content').forEach(function(c) {
    c.classList.remove('active');
  });

  // Activar tab seleccionado
  if (btn) btn.classList.add('active');
  var target = document.getElementById(tabId);
  if (target) target.classList.add('active');
}

// --- CAMBIO DE DISPONIBILIDAD (SWITCH PROFESIONAL) ---
function toggleDisponibilidad(checkbox) {
  var nuevoEstado = checkbox.checked ? 1 : 0;
  var statusTitle = document.getElementById('dispStatusTitle');
  var statusSub = document.getElementById('dispStatusSub');

  fetch('cambiar_disponibilidad.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'disponible=' + nuevoEstado
  })
  .then(function(res) { return res.text(); })
  .then(function(data) {
    if (nuevoEstado === 1) {
      if (statusTitle) statusTitle.textContent = '🟢 Disponible';
      if (statusSub) statusSub.textContent = 'Visible en búsquedas';
      mostrarToast('🟢 Ahora aparecés como disponible para recibir trabajos', 'ok');
    } else {
      if (statusTitle) statusTitle.textContent = '🔴 No disponible';
      if (statusSub) statusSub.textContent = 'Pausado temporalmente';
      mostrarToast('🔴 Disponibilidad pausada: no recibirás nuevas alertas', 'info');
    }
  })
  .catch(function(err) {
    console.error(err);
    checkbox.checked = !checkbox.checked; // Revertir
    mostrarToast('⚠️ Error al actualizar disponibilidad', 'error');
  });
}

// --- GESTIÓN DE SOLICITUDES (PROFESIONAL) ---
function cambiarEstadoSolicitud(solicitudId, nuevoEstado, btn) {
  if (btn) btn.disabled = true;

  fetch('gestionar_solicitud.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(solicitudId) + '&estado=' + encodeURIComponent(nuevoEstado)
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (data.ok) {
      // Actualizar badge visual en la fila/tarjeta de la solicitud
      var badge = document.getElementById('solicitudBadge_' + solicitudId);
      if (badge) {
        badge.className = 'badge-estado-sol ' + nuevoEstado;
        badge.textContent = nuevoEstado;
      }
      mostrarToast('Solicitud actualizada a: ' + nuevoEstado, 'ok');

      // Si se acepta o completa, refrescar suavemente las stats si existen
      var statTotal = document.getElementById('kpi_trabajos_mes');
      if (statTotal && data.trabajos_mes !== undefined) {
        statTotal.textContent = data.trabajos_mes;
      }
    } else {
      mostrarToast('Error: ' + (data.msg || 'No se pudo actualizar'), 'error');
      if (btn) btn.disabled = false;
    }
  })
  .catch(function(err) {
    console.error(err);
    mostrarToast('Error de conexión', 'error');
    if (btn) btn.disabled = false;
  });
}

// --- COPIAR ENLACE AL PORTAPAPELES ---
function copiarEnlacePerfil(url) {
  if (!url) url = window.location.href;
  navigator.clipboard.writeText(url).then(function() {
    mostrarToast('📋 ¡Enlace copiado al portapapeles con éxito!', 'ok');
  }).catch(function() {
    mostrarToast('No se pudo copiar el enlace', 'error');
  });
}

// --- ACTUALIZACIÓN EN TIEMPO REAL DE LA VISTA PREVIA (LIVE PREVIEW) ---
function actualizarLivePreview() {
  var rubroSelect = document.querySelector('select[name="rubro"]');
  var zonaInput = document.querySelector('input[name="zona"]');
  var precioInput = document.querySelector('input[name="precio"]');
  var descInput = document.querySelector('textarea[name="descripcion"]');

  var previewRubro = document.getElementById('previewRubro');
  var previewZona = document.getElementById('previewZona');
  var previewPrecio = document.getElementById('previewPrecio');
  var previewDesc = document.getElementById('previewDesc');

  if (rubroSelect && previewRubro) previewRubro.textContent = rubroSelect.value || 'Especialidad';
  if (zonaInput && previewZona) previewZona.textContent = zonaInput.value || 'Mar del Plata';
  if (precioInput && previewPrecio) previewPrecio.textContent = precioInput.value || 'A convenir';
  if (descInput && previewDesc) previewDesc.textContent = descInput.value || 'Descripción del servicio...';
}

// --- SISTEMA DE TOAST NOTIFICATIONS ---
function mostrarToast(mensaje, tipo) {
  var container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  var toast = document.createElement('div');
  toast.className = 'toast' + (tipo === 'ok' ? ' toast-ok' : (tipo === 'error' ? ' toast-error' : ''));
  
  var icono = tipo === 'ok' ? '✅' : (tipo === 'error' ? '❌' : 'ℹ️');
  toast.innerHTML = '<span>' + icono + '</span> <span>' + mensaje + '</span>';

  container.appendChild(toast);

  // Animar entrada
  setTimeout(function() {
    toast.classList.add('show');
  }, 20);

  // Auto-destrucción a los 3.5 segundos
  setTimeout(function() {
    toast.classList.remove('show');
    setTimeout(function() {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
  }, 3500);
}

// --- VERIFICACIÓN DE PARÁMETROS URL Y ALERTAS ---
function verificarAlertas() {
  var params = new URLSearchParams(window.location.search);
  var error = document.getElementById('alertaError');
  var ok = document.getElementById('alertaOk');

  if (params.get('error') === '1' && error) {
    error.classList.add('show');
    mostrarToast('❌ Verificá los datos ingresados', 'error');
  }
  if (params.get('registro') === 'ok' && ok) {
    ok.classList.add('show');
    mostrarToast('✅ ¡Cuenta creada con éxito! Ya podés iniciar sesión', 'ok');
  }
  if (params.get('guardado') === '1') {
    mostrarToast('💾 ¡Tus cambios fueron guardados exitosamente!', 'ok');
  }
  if (params.get('solicitud') === 'ok') {
    mostrarToast('📬 ¡Solicitud enviada! El profesional se pondrá en contacto a la brevedad', 'ok');
  }
  if (params.get('resena') === 'ok') {
    mostrarToast('⭐ ¡Gracias por tu reseña! Ya fue publicada en el perfil', 'ok');
  }
}

// --- PANEL ADMIN (BÚSQUEDA Y ACTIVACIÓN AJAX) ---
function adminSearch() {
  var q = document.getElementById('adminSearch');
  if (!q) return;
  var txt = q.value.toLowerCase();
  document.querySelectorAll('tbody tr').forEach(function(row) {
    var name = (row.children[1] && row.children[1].textContent) ? row.children[1].textContent.toLowerCase() : '';
    var email = (row.children[2] && row.children[2].textContent) ? row.children[2].textContent.toLowerCase() : '';
    var show = name.includes(txt) || email.includes(txt);
    row.style.display = show ? '' : 'none';
  });
}

function adminToggle(id, accion, btn) {
  var confirmMsg = (accion === 'activar' ? '¿Confirmás habilitar al usuario #' : '¿Confirmás deshabilitar al usuario #') + id + '?';
  if (!confirm(confirmMsg)) return;
  if (btn) btn.disabled = true;

  fetch('admin_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, accion: accion })
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (!data.ok) {
      mostrarToast('Error: ' + (data.msg || 'No se pudo actualizar'), 'error');
      if (btn) btn.disabled = false;
      return;
    }

    var filas = document.querySelectorAll('tbody tr');
    filas.forEach(function(r) {
      var cell = r.querySelector('td');
      if (cell && parseInt(cell.textContent) === id) {
        var estado = r.querySelector('.estado-badge');
        var accionBtn = r.querySelector('button.btn-activar, button.btn-desactivar');
        if (accion === 'activar') {
          if (estado) {
            estado.className = 'estado-badge activo';
            estado.textContent = '● Activo';
          }
          if (accionBtn) {
            accionBtn.textContent = 'Desactivar';
            accionBtn.className = 'btn-desactivar';
            accionBtn.setAttribute('onclick', 'adminToggle(' + id + ', "desactivar", this)');
          }
        } else {
          if (estado) {
            estado.className = 'estado-badge inactivo';
            estado.textContent = '● Inactivo';
          }
          if (accionBtn) {
            accionBtn.textContent = 'Activar';
            accionBtn.className = 'btn-activar';
            accionBtn.setAttribute('onclick', 'adminToggle(' + id + ', "activar", this)');
          }
        }
      }
    });

    if (data.nuevos) {
      if (data.nuevos.total_usuarios !== undefined && document.getElementById('stat_total_usuarios'))
        document.getElementById('stat_total_usuarios').textContent = data.nuevos.total_usuarios;
      if (data.nuevos.total_turistas !== undefined && document.getElementById('stat_turistas'))
        document.getElementById('stat_turistas').textContent = data.nuevos.total_turistas;
      if (data.nuevos.total_profesionales !== undefined && document.getElementById('stat_profesionales'))
        document.getElementById('stat_profesionales').textContent = data.nuevos.total_profesionales;
      if (data.nuevos.total_activos !== undefined && document.getElementById('stat_activos'))
        document.getElementById('stat_activos').textContent = data.nuevos.total_activos;
    }

    mostrarToast('Usuario #' + id + ' actualizado', 'ok');
  })
  .catch(function(err) {
    console.error(err);
    mostrarToast('Error de red', 'error');
    if (btn) btn.disabled = false;
  });
}

// --- INICIALIZACIÓN AL CARGAR EL DOM ---
document.addEventListener('DOMContentLoaded', function() {
  verificarAlertas();
  inicializarStarRating();

  // Escuchar inputs para live preview si estamos en el panel profesional
  var inputsPreview = document.querySelectorAll('select[name="rubro"], input[name="zona"], input[name="precio"], textarea[name="descripcion"]');
  inputsPreview.forEach(function(el) {
    el.addEventListener('input', actualizarLivePreview);
  });
});

