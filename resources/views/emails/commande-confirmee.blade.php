<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commande {{ $commande->numero }} — Paiement confirmé</title>
<style>
  body { margin: 0; padding: 0; background: #f1f5f9; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; }
  .wrapper { max-width: 620px; margin: 32px auto; }
  .header { background: linear-gradient(135deg, #111a30 0%, #1B4332 60%, #0d9488 100%); border-radius: 16px 16px 0 0; padding: 36px 40px; text-align: center; }
  .header h1 { color: #fff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; margin: 0 0 4px; }
  .header p { color: rgba(255,255,255,0.65); font-size: 13px; margin: 0; }
  .badge { display: inline-block; background: #10b981; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; margin-top: 14px; letter-spacing: 1px; text-transform: uppercase; }
  .body { background: #fff; padding: 36px 40px; }
  .greeting { font-size: 18px; font-weight: 700; color: #111a30; margin-bottom: 12px; }
  .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin: 20px 0; }
  .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
  .info-row:last-child { border-bottom: none; }
  .info-row .label { color: #64748b; }
  .info-row .value { font-weight: 600; color: #1e293b; }
  table.articles { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
  table.articles th { background: #f1f5f9; color: #64748b; font-weight: 600; padding: 10px 12px; text-align: left; }
  table.articles td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  .totals-box { border-top: 2px solid #e2e8f0; padding-top: 14px; margin-top: 4px; }
  .total-row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
  .total-row.grand { font-size: 17px; font-weight: 800; color: #0d9488; border-top: 1px solid #e2e8f0; margin-top: 8px; padding-top: 10px; }
  .cta-btn { display: block; text-align: center; background: #0d9488; color: #fff; font-weight: 700; font-size: 15px; text-decoration: none; padding: 16px 32px; border-radius: 12px; margin: 28px 0 0; }
  .eco-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px 18px; margin: 20px 0; font-size: 13px; color: #065f46; }
  .footer { background: #f8fafc; border-radius: 0 0 16px 16px; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <!-- Header -->
  <div class="header">
    <h1>🌿 RETISS</h1>
    <p>Textile circulaire &amp; solidaire</p>
    <div class="badge">✅ Paiement confirmé</div>
  </div>

  <!-- Body -->
  <div class="body">
    <p class="greeting">Bonjour {{ $commande->user->full_name ?: $commande->user->name }},</p>
    <p style="color:#475569;font-size:14px;line-height:1.6;">
      Nous avons bien reçu votre paiement. Votre commande est confirmée et est en cours de préparation.
      Vous trouverez la <strong>facture en pièce jointe</strong> de cet email.
    </p>

    <!-- Order info -->
    <div class="info-box">
      <div class="info-row">
        <span class="label">Numéro de commande</span>
        <span class="value" style="color:#0d9488">{{ $commande->numero }}</span>
      </div>
      <div class="info-row">
        <span class="label">Date de commande</span>
        <span class="value">{{ $commande->date_commande->format('d/m/Y à H:i') }}</span>
      </div>
      <div class="info-row">
        <span class="label">Mode de paiement</span>
        <span class="value">{{ $commande->modePaiementLabel() }}</span>
      </div>
      @if($commande->date_livraison_estimee)
      <div class="info-row">
        <span class="label">Livraison estimée</span>
        <span class="value" style="color:#0d9488">{{ $commande->date_livraison_estimee->format('d/m/Y') }}</span>
      </div>
      @endif
      @if($commande->adresse_snapshot)
      <div class="info-row">
        <span class="label">Adresse de livraison</span>
        <span class="value" style="text-align:right;max-width:280px;">{{ $commande->adresse_snapshot }}</span>
      </div>
      @endif
    </div>

    <!-- Articles table -->
    <p style="font-weight:700;font-size:15px;color:#111a30;margin:20px 0 8px;">Articles commandés</p>
    <table class="articles">
      <thead>
        <tr>
          <th>Article</th>
          <th style="text-align:center">Qté</th>
          <th style="text-align:right">Prix unit.</th>
          @if($commande->remise > 0)
          <th style="text-align:right">Remise</th>
          @endif
          <th style="text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($commande->lignes as $ligne)
        <tr>
          <td>
            <strong>{{ $ligne->article?->titre ?? 'Article supprimé' }}</strong>
            @if($ligne->article?->user)
            <br><span style="color:#94a3b8;font-size:11px;">Vendeur : {{ $ligne->article->user->full_name }}</span>
            @endif
          </td>
          <td style="text-align:center">{{ $ligne->quantite }}</td>
          <td style="text-align:right">{{ number_format($ligne->prix_unitaire, 2) }} €</td>
          @if($commande->remise > 0)
          <td style="text-align:right;color:#10b981">
            {{ $ligne->remise > 0 ? '-'.number_format($ligne->remise, 2).' €' : '—' }}
          </td>
          @endif
          <td style="text-align:right;font-weight:700">{{ number_format($ligne->total_ligne, 2) }} €</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-box">
      <div class="total-row">
        <span>Sous-total</span>
        <span>{{ number_format($commande->montant_sous_total, 2) }} €</span>
      </div>
      @if($commande->remise > 0)
      <div class="total-row" style="color:#10b981">
        <span>Remise (10 %)</span>
        <span>-{{ number_format($commande->remise, 2) }} €</span>
      </div>
      @endif
      <div class="total-row">
        <span>Frais de livraison</span>
        <span>{{ number_format($commande->frais_livraison, 2) }} €</span>
      </div>
      <div class="total-row grand">
        <span>Total payé</span>
        <span>{{ number_format($commande->montant_total, 2) }} €</span>
      </div>
    </div>

    <!-- Eco impact -->
    @php $eco = $commande->impactEcologique(); @endphp
    <div class="eco-box">
      🌱 En achetant d'occasion, vous avez économisé environ <strong>{{ $eco['co2_kg'] }} kg de CO₂</strong>
      et <strong>{{ number_format($eco['eau_litres'], 0, ',', ' ') }} litres d'eau</strong>.
      Merci pour votre engagement solidaire !
    </div>

    <!-- CTA -->
    <a href="{{ route('commandes.show', $commande) }}" class="cta-btn">
      📦 Suivre ma commande en direct
    </a>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p><strong>RETISS</strong> — Textile circulaire &amp; solidaire</p>
    <p>Vous recevez cet email car vous avez passé une commande sur notre plateforme.<br>
       En cas de question, répondez à cet email ou contactez notre support.</p>
  </div>
</div>
</body>
</html>

@php use App\Models\Commande; @endphp
