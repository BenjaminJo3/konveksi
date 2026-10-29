import './bootstrap';
import JsBarcode from 'jsbarcode';

const notificationTrigger = document.querySelector('[data-notification-trigger]');
if (notificationTrigger instanceof HTMLElement) {
    notificationTrigger.addEventListener('click', async (event) => {
        const details = notificationTrigger.closest('details');
        if (!(details instanceof HTMLDetailsElement) || details.open) {
            return;
        }

        const errorMessage = details.querySelector('[data-notification-error]');
        if (errorMessage instanceof HTMLElement) {
            errorMessage.hidden = true;
        }

        try {
            const response = await fetch(notificationTrigger.dataset.readUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`Tandai notifikasi gagal (${response.status}).`);
            }

            const result = await response.json();
            const badge = document.querySelector('#notification-unread-count');
            const summary = document.querySelector('#notification-unread-summary');
            if (result.unread_count === 0) {
                badge?.remove();
                summary?.remove();
            } else {
                if (badge instanceof HTMLElement) {
                    badge.textContent = result.unread_count > 99 ? '99+' : String(result.unread_count);
                }
                if (summary instanceof HTMLElement) {
                    summary.textContent = `${result.unread_count} belum dibaca`;
                }
            }
        } catch (error) {
            if (errorMessage instanceof HTMLElement) {
                errorMessage.hidden = false;
            }
            console.error(error);
        }
    });
}

const sidebarToggle = document.querySelector('#sidebar-toggle');
const sidebar = document.querySelector('#app-sidebar');
const sidebarBackdrop = document.querySelector('#sidebar-backdrop');
const workspace = document.querySelector('#workspace');
if (sidebarToggle instanceof HTMLButtonElement
    && sidebar instanceof HTMLElement
    && sidebarBackdrop instanceof HTMLButtonElement
    && workspace instanceof HTMLElement) {
    const closeMobileSidebar = () => {
        sidebar.classList.remove('mobile-sidebar-open');
        sidebarBackdrop.classList.remove('sidebar-backdrop-open');
        document.body.classList.remove('overflow-hidden');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebarToggle.setAttribute('aria-label', 'Buka navigasi');
    };

    sidebarToggle.addEventListener('click', () => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            workspace.classList.toggle('sidebar-hidden');
            sidebarToggle.setAttribute('aria-expanded', String(!workspace.classList.contains('sidebar-hidden')));
            sidebarToggle.setAttribute('aria-label', workspace.classList.contains('sidebar-hidden') ? 'Buka navigasi' : 'Sembunyikan navigasi');
            return;
        }

        const isOpen = sidebar.classList.toggle('mobile-sidebar-open');
        sidebarBackdrop.classList.toggle('sidebar-backdrop-open', isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen);
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        sidebarToggle.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
    });

    sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
        if (!window.matchMedia('(min-width: 1024px)').matches) {
            closeMobileSidebar();
        }
    }));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            closeMobileSidebar();
        }
    });
}

const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
const sidebarNav = document.querySelector('.workspace-sidebar nav');
const sidebarScrollKey = 'ruang-produksi-sidebar-scroll';
const sidebarContainer = document.querySelector('#app-sidebar');
if (sidebarNav instanceof HTMLElement) {
    let activeLinks = 0;
    sidebarNav.querySelectorAll('a[href]').forEach((link) => {
        const targetPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        if (currentPath === targetPath || (targetPath !== '/' && currentPath.startsWith(`${targetPath}/`))) {
            link.classList.add('nav-link-active');
            link.setAttribute('aria-current', 'page');
            activeLinks += 1;
        }
    });
    if (activeLinks > 0) {
        sidebarNav.querySelectorAll('details').forEach((details) => {
            if (details.querySelector('a.nav-link-active')) {
                details.open = true;
                details.querySelector('summary')?.classList.add('nav-link-active');
            }
        });
    }

    sidebarNav.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', () => {
        if (sidebarContainer instanceof HTMLElement && link.origin === window.location.origin) {
            sessionStorage.setItem(sidebarScrollKey, String(sidebarContainer.scrollTop));
        }
    }));
}
if (sidebarContainer instanceof HTMLElement) {
    const savedScroll = sessionStorage.getItem(sidebarScrollKey);
    if (savedScroll !== null) {
        requestAnimationFrame(() => {
            sidebarContainer.scrollTop = Number(savedScroll);
            sessionStorage.removeItem(sidebarScrollKey);
        });
    }
}

const kanbanBoard = document.querySelector('[data-kanban-board]');
if (kanbanBoard instanceof HTMLElement) {
    const kanbanScrollKey = 'ruang-produksi-kanban-scroll-left';
    const savedBoardScroll = sessionStorage.getItem(kanbanScrollKey);
    if (savedBoardScroll !== null) {
        kanbanBoard.scrollLeft = Number(savedBoardScroll);
        sessionStorage.removeItem(kanbanScrollKey);
    }

    const refreshBoard = () => {
        sessionStorage.setItem(kanbanScrollKey, String(kanbanBoard.scrollLeft));
        window.location.reload();
    };
    document.querySelector('[data-kanban-refresh]')?.addEventListener('click', refreshBoard);
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshBoard();
        }
    }, 60_000);
}

const productCategory = document.querySelector('#category_id');
const productSubCategory = document.querySelector('#sub_category_id');
const productModel = document.querySelector('#model_id');
const productSize = document.querySelector('#size_id');
const productColor = document.querySelector('#color_id');
const productName = document.querySelector('#name');
if (productCategory instanceof HTMLSelectElement
    && productSubCategory instanceof HTMLSelectElement) {
    const filterOptions = (select, predicate) => {
        [...select.options].forEach((option, index) => {
            if (index === 0) {
                return;
            }
            option.hidden = !predicate(option);
            option.disabled = option.hidden;
        });

        if (select.selectedOptions[0]?.hidden) {
            select.value = '';
        }
    };
    const updateMasterOptions = () => {
        filterOptions(productSubCategory, (option) => option.dataset.category === productCategory.value);
        if (productModel instanceof HTMLSelectElement) {
            filterOptions(productModel, (option) => option.dataset.category === productCategory.value
                && option.dataset.subCategory === productSubCategory.value);
        }
        if (productName instanceof HTMLInputElement
            && productModel instanceof HTMLSelectElement
            && productSize instanceof HTMLSelectElement
            && productColor instanceof HTMLSelectElement
            && productSubCategory.value
            && productModel.value
            && productSize.value
            && productColor.value) {
            productName.value = [
                productSubCategory.selectedOptions[0].textContent.trim(),
                productColor.selectedOptions[0].textContent.trim(),
                productModel.selectedOptions[0].textContent.trim(),
                productSize.selectedOptions[0].textContent.trim(),
            ].join(' ');
        } else if (productName instanceof HTMLInputElement) {
            productName.value = '';
        }
    };

    productCategory.addEventListener('change', updateMasterOptions);
    productSubCategory.addEventListener('change', updateMasterOptions);
    productModel?.addEventListener('change', updateMasterOptions);
    productSize?.addEventListener('change', updateMasterOptions);
    productColor?.addEventListener('change', updateMasterOptions);
    updateMasterOptions();
}

const barcodePaper = document.querySelector('#barcode-paper');
const barcodeCount = document.querySelector('#barcode-count');
const printBarcodesButton = document.querySelector('#print-barcodes');
const barcodeWidthInput = document.querySelector('#paper-width');
const barcodeHeightInput = document.querySelector('#paper-height');
const barcodeColumnsInput = document.querySelector('#labels-per-row');
if (barcodePaper instanceof HTMLElement
    && barcodeCount instanceof HTMLElement
    && printBarcodesButton instanceof HTMLButtonElement
    && barcodeWidthInput instanceof HTMLInputElement
    && barcodeHeightInput instanceof HTMLInputElement
    && barcodeColumnsInput instanceof HTMLInputElement) {
    const selectedBarcodes = new Map();
    const printSettings = document.querySelector('#barcode-print-settings');
    const updateBarcodePreview = () => {
        const paperWidth = Number(barcodeWidthInput.value);
        const paperHeight = Number(barcodeHeightInput.value);
        const columns = Number(barcodeColumnsInput.value);
        const totalLabels = [...selectedBarcodes.values()].reduce((total, item) => total + item.quantity, 0);
        const validSettings = paperWidth >= 50 && paperWidth <= 500
            && paperHeight >= 50 && paperHeight <= 500
            && Number.isInteger(columns) && columns >= 1 && columns <= 10;
        const totalRows = validSettings && totalLabels > 0 ? Math.ceil(totalLabels / columns) : 0;
        const rowsPerPage = validSettings ? Math.max(1, Math.floor((paperHeight - 10 + 2) / 29)) : 0;
        const pages = totalRows > 0 ? Math.ceil(totalRows / rowsPerPage) : 0;

        barcodePaper.replaceChildren();
        barcodePaper.style.width = validSettings ? `${paperWidth}mm` : '100%';
        barcodePaper.style.minHeight = validSettings ? `${paperHeight}mm` : '';
        barcodePaper.style.gridTemplateColumns = `repeat(${validSettings ? columns : 1}, minmax(0, 1fr))`;
        barcodeCount.textContent = validSettings
            ? `${totalLabels} label · ${totalRows} baris · ${columns} kotak/baris · ±${pages} halaman · kertas ${paperWidth} × ${paperHeight} mm`
            : 'Ukuran kertas harus 50–500 mm dan jumlah kotak 1–10.';
        printBarcodesButton.disabled = !validSettings || totalLabels === 0;

        if (printSettings instanceof HTMLStyleElement && validSettings) {
            printSettings.textContent = `@page { size: ${paperWidth}mm ${paperHeight}mm; margin: 0 !important; @top-left { content: ""; } @top-center { content: ""; } @top-right { content: ""; } @bottom-left { content: ""; } @bottom-center { content: ""; } @bottom-right { content: ""; } }`;
        }

        selectedBarcodes.forEach((item) => {
            for (let copy = 0; copy < item.quantity; copy += 1) {
                const label = document.createElement('article');
                label.className = 'barcode-label';
                const productName = document.createElement('p');
                productName.className = 'barcode-product-name';
                productName.textContent = item.name;
                const variant = document.createElement('p');
                variant.className = 'barcode-variant';
                variant.textContent = item.variant;
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.classList.add('barcode-symbol');
                JsBarcode(svg, item.code, {
                    format: 'CODE128',
                    displayValue: false,
                    height: 34,
                    margin: 0,
                    width: 1.35,
                    lineColor: '#0f172a',
                    background: '#ffffff',
                });
                const code = document.createElement('p');
                code.className = 'barcode-code';
                code.textContent = item.code;
                label.append(productName, variant, svg, code);
                barcodePaper.append(label);
            }
        });
    };

    document.querySelectorAll('[data-add-barcode]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('article');
            const quantityInput = row?.querySelector('input[type="number"]');
            const quantity = quantityInput instanceof HTMLInputElement ? Number(quantityInput.value) : 0;
            const code = button.dataset.code;
            if (!code || !Number.isInteger(quantity) || quantity < 1 || quantity > 500) {
                barcodeCount.textContent = 'Jumlah label harus antara 1 sampai 500.';
                return;
            }

            const currentTotal = [...selectedBarcodes.values()].reduce((total, item) => total + item.quantity, 0);
            if (currentTotal + quantity > 500) {
                barcodeCount.textContent = 'Maksimal 500 label per sekali cetak.';
                return;
            }

            const selected = selectedBarcodes.get(code);
            selectedBarcodes.set(code, {
                code,
                name: button.dataset.name ?? code,
                variant: button.dataset.variant ?? '',
                quantity: (selected?.quantity ?? 0) + quantity,
            });
            updateBarcodePreview();
        });
    });

    barcodeWidthInput.addEventListener('input', updateBarcodePreview);
    barcodeHeightInput.addEventListener('input', updateBarcodePreview);
    barcodeColumnsInput.addEventListener('input', updateBarcodePreview);
    document.querySelector('#clear-barcodes')?.addEventListener('click', () => {
        selectedBarcodes.clear();
        updateBarcodePreview();
    });
    printBarcodesButton.addEventListener('click', () => {
        updateBarcodePreview();
        window.print();
    });
    const originalTitle = document.title;
    window.addEventListener('beforeprint', () => {
        document.title = '';
    });
    window.addEventListener('afterprint', () => {
        document.title = originalTitle;
    });
    updateBarcodePreview();
}

document.querySelectorAll('[data-demo-email]').forEach((button) => {
    button.addEventListener('click', () => {
        const email = document.querySelector('#email');
        const password = document.querySelector('#password');

        if (!(email instanceof HTMLInputElement) || !(password instanceof HTMLInputElement)) {
            return;
        }

        email.value = button.dataset.demoEmail ?? '';
        password.value = button.dataset.demoPassword ?? '';
        email.focus();
    });

    const signatureCanvas = document.querySelector('[data-signature-canvas]');
    const signatureForm = document.querySelector('[data-signature-form]');
    const signatureData = document.querySelector('[data-signature-data]');
    if (signatureCanvas instanceof HTMLCanvasElement
        && signatureForm instanceof HTMLFormElement
        && signatureData instanceof HTMLInputElement) {
        const context = signatureCanvas.getContext('2d');
        let isDrawing = false;
        let hasSignature = false;

        if (context) {
            const resizeCanvas = () => {
                const existingSignature = hasSignature ? signatureCanvas.toDataURL('image/png') : null;
                const bounds = signatureCanvas.getBoundingClientRect();
                if (!bounds.width || !bounds.height) {
                    return;
                }

                const pixelRatio = Math.max(window.devicePixelRatio || 1, 1);
                signatureCanvas.width = Math.round(bounds.width * pixelRatio);
                signatureCanvas.height = Math.round(bounds.height * pixelRatio);
                context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
                context.lineWidth = 2.4;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#172033';

                if (existingSignature) {
                    const image = new Image();
                    image.onload = () => context.drawImage(image, 0, 0, bounds.width, bounds.height);
                    image.src = existingSignature;
                }
            };
            const pointerPosition = (event) => {
                const bounds = signatureCanvas.getBoundingClientRect();
                return { x: event.clientX - bounds.left, y: event.clientY - bounds.top };
            };

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            signatureCanvas.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                signatureCanvas.setPointerCapture(event.pointerId);
                const point = pointerPosition(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
                isDrawing = true;
                hasSignature = true;
            });
            signatureCanvas.addEventListener('pointermove', (event) => {
                if (!isDrawing) {
                    return;
                }

                event.preventDefault();
                const point = pointerPosition(event);
                context.lineTo(point.x, point.y);
                context.stroke();
            });
            const stopDrawing = () => {
                isDrawing = false;
            };
            signatureCanvas.addEventListener('pointerup', stopDrawing);
            signatureCanvas.addEventListener('pointercancel', stopDrawing);
            signatureCanvas.addEventListener('lostpointercapture', stopDrawing);
            signatureForm.querySelector('[data-signature-clear]')?.addEventListener('click', () => {
                context.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                hasSignature = false;
                signatureData.value = '';
            });
            signatureForm.addEventListener('submit', (event) => {
                const signatureFile = signatureForm.querySelector('input[name="signature_image"]');
                if (signatureFile instanceof HTMLInputElement && signatureFile.files?.length) {
                    return;
                }
                if (!hasSignature) {
                    event.preventDefault();
                    window.alert('Silakan bubuhkan tanda tangan sebelum menyimpan SPK.');
                    return;
                }
                signatureData.value = signatureCanvas.toDataURL('image/jpeg', 0.92);
            });
        }
    }
});

const poItems = document.querySelector('#po-items');
const poItemTemplate = document.querySelector('#po-item-template');
if (poItems instanceof HTMLElement && poItemTemplate instanceof HTMLTemplateElement) {
    const categoryList = document.querySelector('#po-categories');
    const subCategoryList = document.querySelector('#po-sub-categories');
    const modelList = document.querySelector('#po-models');
    const productList = document.querySelector('#po-products');
    const masterOptions = (list) => [...list.options].map((option) => option.cloneNode(true));
    const categories = masterOptions(categoryList);
    const subCategories = masterOptions(subCategoryList);
    const models = masterOptions(modelList);
    const products = masterOptions(productList);
    const normalized = (value) => value.trim().toLocaleLowerCase();
    const matchingOption = (options, value) => options.find((option) => normalized(option.value) === normalized(value));
    const updateMasterSuggestions = (item) => {
        const categoryInput = item.querySelector('[data-po-category]');
        const subCategoryInput = item.querySelector('[data-po-sub-category]');
        const modelInput = item.querySelector('[name$="[model]"]');
        const category = matchingOption(categories, categoryInput.value);
        const visibleSubCategories = category
            ? subCategories.filter((option) => option.dataset.categoryId === category.dataset.categoryId)
            : [];
        const knownSubCategory = matchingOption(subCategories, subCategoryInput.value);
        let subCategory = matchingOption(visibleSubCategories, subCategoryInput.value);
        if (knownSubCategory && !subCategory) {
            subCategoryInput.value = '';
            subCategory = null;
        }
        const visibleModels = models.filter((option) => category
            && option.dataset.categoryId === category.dataset.categoryId
            && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId));
        const knownModel = matchingOption(models, modelInput.value);
        let model = matchingOption(visibleModels, modelInput.value);
        if (knownModel && !model) {
            modelInput.value = '';
            model = null;
        }
        const visibleProducts = products.filter((option) => category
            && option.dataset.categoryId === category.dataset.categoryId
            && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId)
            && (!model || option.dataset.modelId === model.dataset.modelId));
        const productInput = item.querySelector('[data-po-product]');
        const knownProduct = matchingOption(products, productInput.value);
        if (knownProduct && !visibleProducts.some((option) => normalized(option.value) === normalized(productInput.value))) {
            productInput.value = '';
        }

        subCategoryList.replaceChildren(...visibleSubCategories.map((option) => option.cloneNode(true)));
        modelList.replaceChildren(...visibleModels.map((option) => option.cloneNode(true)));
        productList.replaceChildren(...visibleProducts.map((option) => option.cloneNode(true)));
    };

    const updateItems = () => {
        const cards = [...poItems.querySelectorAll('[data-item]')];

        cards.forEach((card, index) => {
            card.querySelector('[data-item-number]').textContent = String(index + 1);
            card.querySelector('[data-remove-item]').classList.toggle('hidden', cards.length === 1);
            card.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
            });

            const yard = Number(card.querySelector('[name$="[yard_kg]"]').value);
            const ratio = Number(card.querySelector('[name$="[ratio]"]').value);
            const result = card.querySelector('[data-ratio-result]');
            result.textContent = yard > 0 && ratio > 0
                ? `${Math.round(yard / ratio).toLocaleString('id-ID')} pcs`
                : '— pcs';
        });
    };

    document.querySelector('#add-po-item').addEventListener('click', () => {
        const index = poItems.querySelectorAll('[data-item]').length;
        const fragment = poItemTemplate.content.cloneNode(true);

        fragment.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replaceAll('__INDEX__', String(index));
        });
        poItems.append(fragment);
        updateMasterSuggestions(poItems.lastElementChild);
        updateItems();
    });

    poItems.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-item]');
        if (button && poItems.querySelectorAll('[data-item]').length > 1) {
            button.closest('[data-item]').remove();
            updateItems();
        }
    });
    poItems.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const item = input.closest('[data-item]');
        if (!item) {
            return;
        }

        if (input.matches('[data-po-category], [data-po-sub-category], [name$="[model]"]')) {
            updateMasterSuggestions(item);
        }
        if (!input.matches('[data-po-product]')) {
            return;
        }

        const category = matchingOption(categories, item.querySelector('[data-po-category]').value);
        const subCategory = matchingOption(subCategories, item.querySelector('[data-po-sub-category]').value);
        const model = matchingOption(models, item.querySelector('[name$="[model]"]').value);
        const product = products.find((option) => option.value === input.value
            && (!category || option.dataset.categoryId === category.dataset.categoryId)
            && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId)
            && (!model || option.dataset.modelId === model.dataset.modelId));
        if (!product) {
            return;
        }

        const fields = {
            category: product.dataset.category,
            sub_category: product.dataset.subCategory,
            model: product.dataset.model,
            size: product.dataset.size,
            color: product.dataset.color,
            fabric_name: product.dataset.fabric,
        };
        Object.entries(fields).forEach(([name, value]) => {
            const field = item.querySelector(`[name$="[${name}]"]`);
            if (field instanceof HTMLInputElement && value) {
                field.value = value;
            }
        });
        updateMasterSuggestions(item);
    });
    poItems.addEventListener('input', (event) => {
        const input = event.target;
        if (input instanceof HTMLInputElement && input.matches('[data-po-category], [data-po-sub-category], [name$="[model]"]')) {
            updateMasterSuggestions(input.closest('[data-item]'));
        }
        updateItems();
    });
    poItems.addEventListener('focusin', (event) => {
        const input = event.target;
        if (input instanceof HTMLInputElement) {
            updateMasterSuggestions(input.closest('[data-item]'));
        }
    });
    updateMasterSuggestions(poItems.querySelector('[data-item]'));
    updateItems();
}

const poNumberMode = document.querySelector('#po_number_mode');
const poNumberInput = document.querySelector('#po_number');
if (poNumberMode instanceof HTMLSelectElement && poNumberInput instanceof HTMLInputElement) {
    const updatePoNumber = () => {
        const manual = poNumberMode.value === 'manual';
        poNumberInput.disabled = !manual;
        poNumberInput.required = manual;
        document.querySelector('#po-number-hint').textContent = manual
            ? 'Nomor ini harus unik dan tidak dapat diubah setelah PO dibuat.'
            : 'Nomor dibuat otomatis saat PO disimpan.';
    };

    poNumberMode.addEventListener('change', updatePoNumber);
    updatePoNumber();
}

const deliverySource = document.querySelector('#source_role');
const deliveryDestination = document.querySelector('#destination_role');
if ((deliverySource instanceof HTMLSelectElement || deliverySource instanceof HTMLInputElement)
    && deliveryDestination instanceof HTMLSelectElement) {
    const updateDestinations = () => {
        const sourceRole = deliverySource.value;

        deliveryDestination.querySelectorAll('[data-destination-role]').forEach((option) => {
            const isActiveLocation = option.dataset.destinationRole === sourceRole;
            option.disabled = isActiveLocation;
            option.hidden = isActiveLocation;

            if (isActiveLocation && option.selected) {
                deliveryDestination.value = '';
            }
        });
    };

    if (deliverySource instanceof HTMLSelectElement) {
        deliverySource.addEventListener('change', updateDestinations);
    }
    updateDestinations();
}

const purchaseOrderSelect = document.querySelector('#po_number');
const purchaseOrderGrid = document.querySelector('#po-item-grid');
const purchaseOrderGridWrap = document.querySelector('#po-item-grid-wrap');
const purchaseOrderItemTemplate = document.querySelector('#po-do-item-template');
const purchaseOrderItemCount = document.querySelector('#po-item-count');
const purchaseOrderQuantityHeading = document.querySelector('#po-quantity-heading');
if (purchaseOrderSelect instanceof HTMLSelectElement
    && purchaseOrderGrid instanceof HTMLElement
    && purchaseOrderGridWrap instanceof HTMLElement
    && purchaseOrderItemTemplate instanceof HTMLTemplateElement
    && purchaseOrderItemCount instanceof HTMLElement
    && purchaseOrderQuantityHeading instanceof HTMLElement) {
    let requestNumber = 0;
    const renderPurchaseOrderItems = async () => {
        const currentRequest = ++requestNumber;
        purchaseOrderGrid.replaceChildren();
        purchaseOrderGridWrap.classList.add('hidden');
        purchaseOrderItemCount.textContent = '';

        if (!purchaseOrderSelect.value) {
            return;
        }

        const endpoint = purchaseOrderSelect.dataset.itemsUrl;
        if (!endpoint) {
            throw new Error('URL item PO tidak tersedia.');
        }

        try {
            const response = await fetch(endpoint.replace('__PO_NUMBER__', encodeURIComponent(purchaseOrderSelect.value)), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error('Item PO gagal dimuat. Muat ulang halaman lalu coba lagi.');
            }

            const payload = await response.json();
            if (currentRequest !== requestNumber) {
                return;
            }
            if (!Array.isArray(payload.items) || payload.items.length === 0) {
                throw new Error('PO ini tidak memiliki item aktif untuk dikirim.');
            }

            payload.items.forEach((item, index) => {
                const row = purchaseOrderItemTemplate.content.firstElementChild.cloneNode(true);
                row.querySelector('[data-cell="number"]').textContent = String(index + 1);
                row.querySelector('[data-cell="category"]').textContent = item.category ?? '—';
                row.querySelector('[data-cell="product"]').textContent = item.product_name ?? '—';
                row.querySelector('[data-cell="model"]').textContent = item.model ?? '—';
                row.querySelector('[data-cell="size"]').textContent = item.size ?? '—';
                row.querySelector('[data-cell="color"]').textContent = item.color ?? '—';
                row.querySelector('[data-cell="fabric_name"]').textContent = item.fabric_name ?? '—';
                row.querySelector('[data-cell="target"]').textContent = `${Number(item.order_quantity).toLocaleString('id-ID')} pcs`;

                const quantityInput = row.querySelector('[data-cell="quantity"]');
                quantityInput.name = `items[${index}][quantity]`;
                quantityInput.setAttribute('aria-label', `QTY kirim ${item.product_name}, ${item.size ?? ''}, ${item.color ?? ''}`);

                const orderId = document.createElement('input');
                orderId.type = 'hidden';
                orderId.name = `items[${index}][production_order_id]`;
                orderId.value = String(item.id);
                row.append(orderId);
                purchaseOrderGrid.append(row);
            });

            purchaseOrderItemCount.textContent = `${payload.items.length} item`;
            purchaseOrderGridWrap.classList.remove('hidden');
        } catch (error) {
            if (currentRequest !== requestNumber) {
                return;
            }
            const message = error instanceof Error ? error.message : 'Terjadi kesalahan saat memuat item PO.';
            purchaseOrderGridWrap.classList.remove('hidden');
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 9;
            cell.className = 'px-4 py-6 text-center text-xs text-rose-600';
            cell.textContent = message;
            row.append(cell);
            purchaseOrderGrid.append(row);
        }
    };

    purchaseOrderSelect.addEventListener('change', renderPurchaseOrderItems);
    const goodsTypeSelect = document.querySelector('#goods_type');
    const updateQuantityLabel = () => {
        const unit = goodsTypeSelect instanceof HTMLSelectElement
            ? goodsTypeSelect.selectedOptions[0]?.dataset.unit
            : null;
        purchaseOrderQuantityHeading.textContent = unit ? `QTY kirim (${unit})` : 'QTY kirim';
        purchaseOrderGrid.querySelectorAll('[data-label="QTY kirim"]').forEach((cell) => {
            cell.dataset.label = unit ? `QTY kirim (${unit})` : 'QTY kirim';
        });
    };
    if (goodsTypeSelect instanceof HTMLSelectElement) {
        goodsTypeSelect.addEventListener('change', updateQuantityLabel);
    }
    updateQuantityLabel();
    if (purchaseOrderSelect.value) {
        renderPurchaseOrderItems();
    }
}
