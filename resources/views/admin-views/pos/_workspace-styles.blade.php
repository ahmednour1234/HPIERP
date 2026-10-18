<style>
.pos-modern-body{background:#f1f4f8;color:#243247;font-family:Arial,Tahoma,sans-serif;padding-bottom:0}
.pos-modern-body #header{height:64px;min-height:64px;background:#fff;border-bottom:1px solid #e2e7ef;box-shadow:none}
.pos-modern-body #header .navbar-brand-logo{max-height:46px;width:auto}
.pos-modern-body #content{padding-top:64px;margin:0}
.pos-modern-section{padding:16px 0!important}
.pos-modern-container{max-width:1920px;padding-inline:20px}
.pos-modern-grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(390px,440px);gap:16px;align-items:stretch}
.pos-modern-grid .order--pos-left,.pos-modern-grid .order--pos-right{width:auto;max-width:none;min-width:0;margin:0}
.pos-panel{border:1px solid #dfe5ee;border-radius:14px!important;background:#fff;box-shadow:none;overflow:hidden}
.pos-panel-header{padding:14px 18px;border-bottom:1px solid #edf0f5;background:#fff;flex-shrink:0}
.pos-panel-title{gap:10px;color:#20324d;font-size:16px;font-weight:700}
.pos-panel-title span{width:32px;height:32px;border-radius:9px;background:#eef3fc;color:#244d88}
.pos-products-toolbar{padding:14px 16px!important;background:#fff;z-index:3}
.pos-products-toolbar .form-control,.pos-bill-panel .form-control{min-height:40px;height:40px;border:1px solid #dce3ed!important;border-radius:8px!important;font-size:13px;font-weight:400}
.pos-products-toolbar .input-group-text{background:#fff;border-color:#dce3ed;color:#74849a}
.pos-modern-body .select2-container .select2-selection--single{height:40px;min-height:40px;border-color:#dce3ed;border-radius:8px}
.pos-modern-body .select2-selection--single .select2-selection__rendered{line-height:38px;font-size:13px}
.pos-modern-body .select2-selection--single .select2-selection__arrow{height:38px}
.pos-products-panel #items{padding:0 16px 16px;min-height:0;overflow-y:auto}
.pos-item-wrap{grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:12px;max-block-size:none;overflow:visible;padding:2px}
.pos-product-item{border:1px solid #e3e8ef!important;border-radius:10px!important;height:100%;min-height:0;box-shadow:none}
.pos-product-item:hover{transform:none;border-color:#5498d5!important;box-shadow:0 4px 14px #244d8812}
.pos-product-item_thumb{height:135px;padding:12px;background:#fafbfd}
.pos-product-item_thumb img{object-fit:contain;mix-blend-mode:multiply}
.pos-product-item_content{min-height:100px;padding:10px 12px;text-align:start;align-items:stretch;justify-content:flex-start}
.pos-product-item_title{font-size:13px;font-weight:600;line-height:1.5;min-height:39px;-webkit-line-clamp:2;color:#243247}
.pos-product-item_code{font-size:11px;color:#8591a2;font-weight:400;margin-bottom:6px;overflow-wrap:anywhere}
.pos-product-item_price{margin-top:auto;font-size:15px;font-weight:700;color:#147b68}
.pos-bill-panel .btn{border-radius:8px!important;font-weight:600;min-height:38px;font-size:12px;padding:8px 12px;box-shadow:none}
.pos-bill-panel .btn-success{background:#13866e;border-color:#13866e}
.pos-customer-controls{padding:14px 16px 0}
.pos-customer-controls .mb-3{margin-bottom:12px!important}
.pos-customer-controls .input-label{font-size:12px;color:#778497;margin:0}
.pos-customer-controls #current_customer{color:#243247;font-weight:600}
.pos-customer-controls .btn-danger{background:#fff5f5;border-color:#f6dcdc;color:#c34c55}
.pos-customer-controls a.btn-success{background:#edf7f4;border-color:#d7ebe4;color:#147b68}
.pos-bill-panel #cart{display:flex;flex-direction:column;flex:1;min-height:0}
.pos-cart-scroll{flex:1;min-height:0;overflow-y:auto}
.pos-cart-scroll>.card-body{padding:0 16px 12px}
.pos-cart-table-modern{border:0!important;border-radius:0}
.pos-cart-table{max-block-size:none;overflow:visible}
.pos-cart-table-modern thead th{background:#f5f7fa;color:#7a8799;border:0;padding:10px 8px;font-size:11px;font-weight:600}
.pos-cart-table-modern tbody td{border-top:1px solid #edf0f5;padding:12px 6px;font-size:13px;font-weight:400;vertical-align:middle}
.pos-cart-product{min-width:125px}
.pos-cart-product .media-body h5{font-size:12px;line-height:1.5;overflow-wrap:anywhere}
.pos-cart-product-img{width:34px!important;height:34px!important;object-fit:contain;border-radius:5px}
.pos-bill-panel .qty-width{width:52px;min-height:34px;height:34px;padding:4px;font-weight:600}
.pos-bill-panel .square-btn{width:28px;height:30px;min-height:30px;padding:4px;border:0;background:#fff1f2;color:#cc6370}
.pos-cart-summary{margin:0 16px 12px;padding:12px!important;background:#f8fafc;border:1px solid #edf0f5;border-radius:10px}
.pos-cart-summary dt,.pos-cart-summary dd{font-size:12px;font-weight:400;padding-block:4px;margin-bottom:0;border:0}
.pos-cart-summary dt{color:#778497}
.pos-cart-summary dd{color:#32435a;font-variant-numeric:tabular-nums}
.pos-bill-panel .pos-mini-edit{width:24px;height:24px;min-height:24px!important;padding:0;margin-inline-end:6px;background:#e9f0fa;color:#37649b}
.pos-checkout-footer{flex-shrink:0;border-top:1px solid #e4e9f0;background:#fff;padding:14px 16px 16px}
.pos-checkout-total{align-items:center;margin-bottom:12px}
.pos-checkout-total dt{font-size:14px;font-weight:600;color:#53637a}
.pos-checkout-total dd{margin:0;font-size:23px;font-weight:700;color:#20324d;font-variant-numeric:tabular-nums}
.pos-cart-actions{position:static;display:grid;grid-template-columns:1fr 2fr;gap:10px;margin:0;padding:0}
.pos-cart-actions>div{max-width:none;width:auto;padding:0;margin:0!important}
.pos-bill-panel .pos-cart-action{display:flex;align-items:center;justify-content:center;gap:8px;min-height:46px;font-size:14px}
.pos-bill-panel .pos-cart-action-danger{background:#fff!important;color:#b4515d;border:1px solid #ead8dc!important}
.pos-bill-panel .pos-cart-action-success{background:#13866e!important;border-color:#13866e!important;color:#fff}
.pos-modern-body :focus-visible{outline:3px solid #80b5e6;outline-offset:2px}
@media(min-width:1100px){
 .pos-modern-grid .pos-panel{height:calc(100dvh - 96px);min-height:0;display:flex;flex-direction:column}
 .pos-bill-panel.billing-section-wrap{max-height:none;overflow:hidden}
}
@media(max-width:1099px){
 .pos-modern-grid{grid-template-columns:minmax(0,1fr) minmax(340px,390px)}
 .pos-products-panel #items{max-height:72vh}
 .pos-bill-panel.billing-section-wrap{max-height:none;overflow:visible}
 .pos-checkout-footer{position:sticky;bottom:0;z-index:4;box-shadow:0 -4px 16px #20324d08}
 .pos-cart-scroll{overflow:visible}
}
@media(max-width:767px){
 .pos-modern-grid{grid-template-columns:minmax(0,1fr)}
 .pos-modern-container{padding-inline:10px}
 .pos-modern-section{padding-block:10px!important}
 .pos-products-panel #items{max-height:45vh}
 .pos-item-wrap{grid-template-columns:repeat(auto-fill,minmax(125px,1fr));gap:8px}
 .pos-product-item_thumb{height:100px}
 .pos-products-toolbar .row{row-gap:8px}
}
</style>
