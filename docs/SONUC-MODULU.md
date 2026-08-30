# Sonuç modülü

Sonuç modülü `DOMAIN_SONUC` ile ayrı bir host üzerinden çalışır. Yerel varsayılan:

```text
http://sonuc.tfsfoy.local
```

Yerel makinede bu hostun açılabilmesi için işletim sistemi hosts dosyasında ve Apache sanal hostunda aşağıdaki karşılıkların bulunması gerekir:

```text
# /etc/hosts
127.0.0.1 sonuc.tfsfoy.local

# tfsf-onayliyarismalar-org Apache VirtualHost bloğu
ServerAlias sonuc.tfsfoy.local
```

Apache ayarı değiştirildikten sonra PHP/Apache geliştirme container'ı yeniden yüklenmelidir.

## Değerlendirme akışı

1. Birinci turda jüri üyeleri eserleri birbirlerinden bağımsız olarak 3–9 arasında puanlar.
2. Üye puan kartında puanları görür; jüri isimleri ve puan-jüri eşleşmesi açıklanmaz.
3. Yarışma bitişine kadar yapılan fotoğraf ekleme veya geri çekme işlemi ilgili kategori jüri kesinleştirmelerini yeniden açar. Önceki puanlar denetim için korunur ancak tekrar kesinleştirilene kadar sonuç hesabına girmez.
4. EYS, birinci tur sonuçlarından finalistleri seçerek yüz yüze final turunu oluşturur.
5. Final toplantısında ortak kurul kararı, isteğe bağlı 3–9 puan ve zorunlu kategori sırası kaydedilir.
6. Ödüller final sonucu üzerinden atanır ve yayımlama sonrasında sonuç subdomain'inde gösterilir.

## Yayın güvenliği

- Yalnızca TFSF tarafından sonuçları yayımlanmış yarışmalar listelenir.
- Geri çekilmiş fotoğraflar puan, final ve halka açık sonuç sorgularına dahil edilmez.
- Halka açık fotoğraf uç noktası yalnızca ödül atanmış ve sonucu yayımlanmış eserleri sunar.
