const fs = require('fs');
const path = require('path');

const brands = {
  Gucci: {
    logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Gucci_logo.svg/512px-Gucci_logo.svg.png',
    categories: {
      Handbags: [
        { name: 'Marmont Matelassé Mini Bag', image: 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=800&q=80', desc: 'A softly structured shape and a flap closure with Double G hardware.' },
        { name: 'Dionysus Small Shoulder Bag', image: 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=800&q=80', desc: 'A structured GG Supreme canvas bag with our textured tiger head closure.' },
        { name: 'Ophidia GG Small Tote', image: 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=800&q=80', desc: 'Classic GG canvas meets modern silhouette.' },
        { name: 'Horsebit 1955 Mini Bag', image: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&q=80', desc: 'Recreated from an archival design.' },
        { name: 'Jackie 1961 Small Hobo', image: 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=800&q=80', desc: 'The recognizable shape is presented in GG Supreme canvas.' },
        { name: 'Padlock Small Bamboo', image: 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=800&q=80', desc: 'Structured top handle bag with bamboo handle.' },
        { name: 'GG Embossed Belt Bag', image: 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=800&q=80', desc: 'A belt bag crafted from black leather.' },
        { name: 'Sylvie 1969 Mini Top Handle', image: 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=800&q=80', desc: 'A petite version of the Sylvie 1969.' },
        { name: 'Soho Leather Disco Bag', image: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=800&q=80', desc: 'Compact shoulder bag with a leather tassel.' },
        { name: 'Diana Mini Tote Bag', image: 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=800&q=80', desc: 'Bamboo handle tote with neon bands.' },
      ],
      Accessories: [
        { name: 'Double G Leather Belt', image: 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=800&q=80', desc: 'Smooth leather belt with Double G buckle.' },
        { name: 'GG Jacquard Pattern Scarf', image: 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=800&q=80', desc: 'Wool scarf with GG motif.' },
        { name: 'Lion Head Ring', image: 'https://images.unsplash.com/photo-1605100804763-247f67b2548e?w=800&q=80', desc: 'Aged gold-toned metal ring.' },
        { name: 'Aviator Metal Sunglasses', image: 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800&q=80', desc: 'Classic aviator sunglasses.' },
        { name: 'GG Marmont Card Case', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Matelassé leather card case.' },
        { name: 'Interlocking G Silver Necklace', image: 'https://images.unsplash.com/photo-1599643478524-fb66f70a00ea?w=800&q=80', desc: '925 sterling silver.' },
        { name: 'Leather Gloves with GG', image: 'https://images.unsplash.com/photo-1549405097-400d75a1811e?w=800&q=80', desc: 'Soft leather gloves.' },
        { name: 'Silk Twill Neck Bow', image: 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=800&q=80', desc: 'Flora print silk neck bow.' },
        { name: 'GG Canvas Fedora', image: 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=800&q=80', desc: 'Bucket hat in GG canvas.' },
        { name: 'G-Timeless Watch', image: 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=800&q=80', desc: 'Stainless steel watch.' },
      ]
    }
  },
  Louis_Vuitton: {
    logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/Louis_Vuitton_logo_and_wordmark.svg/512px-Louis_Vuitton_logo_and_wordmark.svg.png',
    name: 'Louis Vuitton',
    categories: {
      Luggage: [
        { name: 'Keepall Bandoulière 55', image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&q=80', desc: 'Classic travel bag in Monogram canvas.' },
        { name: 'Horizon 55', image: 'https://images.unsplash.com/photo-1565026057447-bc90a3dce187?w=800&q=80', desc: 'Rolling luggage in Damier Graphite.' },
        { name: 'Pegase Légère 55', image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&q=80', desc: 'Elegant suitcase.' },
        { name: 'Sirius 55', image: 'https://images.unsplash.com/photo-1565026057447-bc90a3dce187?w=800&q=80', desc: 'Soft-sided travel bag.' },
        { name: 'City Steamer XXL', image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&q=80', desc: 'Large tote for travel.' },
        { name: 'Alzer 70', image: 'https://images.unsplash.com/photo-1565026057447-bc90a3dce187?w=800&q=80', desc: 'Hard-sided trunk.' },
        { name: 'Bisten 60', image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&q=80', desc: 'Classic hard suitcase.' },
        { name: 'Cotteville 40', image: 'https://images.unsplash.com/photo-1565026057447-bc90a3dce187?w=800&q=80', desc: 'Vintage style trunk.' },
        { name: 'Cruiser Bag 45', image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&q=80', desc: 'Spacious soft bag.' },
        { name: 'Eole 50', image: 'https://images.unsplash.com/photo-1565026057447-bc90a3dce187?w=800&q=80', desc: 'Rolling duffle bag.' },
      ],
      Wallets: [
        { name: 'Zippy Wallet', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Iconic zip-around wallet.' },
        { name: 'Multiple Wallet', image: 'https://images.unsplash.com/photo-1559520448-6a56e974e6f4?w=800&q=80', desc: 'Compact bifold.' },
        { name: 'Brazza Wallet', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Long breast pocket wallet.' },
        { name: 'Slender Wallet', image: 'https://images.unsplash.com/photo-1559520448-6a56e974e6f4?w=800&q=80', desc: 'Slim profile wallet.' },
        { name: 'Pocket Organizer', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Compact card holder.' },
        { name: 'Sarah Wallet', image: 'https://images.unsplash.com/photo-1559520448-6a56e974e6f4?w=800&q=80', desc: 'Envelope-style wallet.' },
        { name: 'Victorine Wallet', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Small trifold.' },
        { name: 'Coin Purse', image: 'https://images.unsplash.com/photo-1559520448-6a56e974e6f4?w=800&q=80', desc: 'Zip coin purse.' },
        { name: 'Marco Wallet', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80', desc: 'Square bifold.' },
        { name: 'Key Pouch', image: 'https://images.unsplash.com/photo-1559520448-6a56e974e6f4?w=800&q=80', desc: 'Small pouch for keys and cards.' },
      ]
    }
  },
  Nike: {
    logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/512px-Logo_NIKE.svg.png',
    categories: {
      Sneakers: [
        { name: 'Air Force 1 \'07', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'Classic basketball sneaker.' },
        { name: 'Air Max 270', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Lifestyle Air Max.' },
        { name: 'Dunk Low Retro', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: '80s hoops icon.' },
        { name: 'Air Jordan 1 Mid', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Everyday Jordan style.' },
        { name: 'Blazer Mid \'77', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'Vintage hoops look.' },
        { name: 'Air Max 90', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Classic 90s running shoe.' },
        { name: 'Pegasus 40', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Responsive running shoe.' },
        { name: 'React Infinity', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Cushioned running shoe.' },
        { name: 'VaporMax 2023', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'Innovative Air cushioning.' },
        { name: 'ZoomX Invincible', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Maximum cushioning.' },
      ],
      Activewear: [
        { name: 'Sportswear Club Fleece', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Classic hoodie.' },
        { name: 'Pro Dri-FIT Shirt', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Tight fit training shirt.' },
        { name: 'Tech Fleece Joggers', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Lightweight warmth.' },
        { name: 'Dri-FIT Challenger', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Running shorts.' },
        { name: 'Windrunner Jacket', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Classic windbreaker.' },
        { name: 'Pro Tights', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Base layer tights.' },
        { name: 'Element Half-Zip', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Running top.' },
        { name: 'Flex Stride Shorts', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Breathable shorts.' },
        { name: 'Therma-FIT Hoodie', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Winter training hoodie.' },
        { name: 'AeroSwift Singlet', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Racing tank top.' },
      ]
    }
  },
  Adidas: {
    logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/512px-Adidas_Logo.svg.png',
    categories: {
      Running_Shoes: [
        { name: 'Ultraboost Light', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Lightweight energy return.' },
        { name: 'Adizero Boston 12', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Training and racing shoe.' },
        { name: 'Supernova 3', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Everyday comfort.' },
        { name: '4DFWD 3', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: '3D printed midsole.' },
        { name: 'Solarboost 5', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Stable running shoe.' },
        { name: 'Adizero Adios Pro 3', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Marathon racing shoe.' },
        { name: 'Duramo SL', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Lightweight runner.' },
        { name: 'Pureboost 23', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'City running shoe.' },
        { name: 'Terrex Agravic', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Trail running shoe.' },
        { name: 'Runfalcon 3', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Daily trainer.' },
      ],
      Hoodies: [
        { name: 'Essentials Fleece Hoodie', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Classic 3-stripes hoodie.' },
        { name: 'Trefoil Hoodie', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Originals logo hoodie.' },
        { name: 'Z.N.E. Premium', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Premium athletic wear.' },
        { name: 'Tiro 23 Track Jacket', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Soccer training jacket.' },
        { name: 'Adicolor Classics', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Vintage style track top.' },
        { name: 'Future Icons', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Modern sportswear hoodie.' },
        { name: 'Own The Run Hoodie', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Running layer.' },
        { name: 'Terrex Multi', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Outdoor fleece.' },
        { name: 'Basketball Fleece', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Heavyweight hoodie.' },
        { name: 'Designed for Training', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Workout hoodie.' },
      ]
    }
  },
  Puma: {
    logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Puma-Logo.png/512px-Puma-Logo.png',
    categories: {
      Training_Shoes: [
        { name: 'PWRFrame TR 2', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'High-intensity training shoe.' },
        { name: 'Fuse 2.0', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Strength training shoe.' },
        { name: 'Disperse XT 2', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Versatile workout shoe.' },
        { name: 'Softride Enzo', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'Cushioned trainer.' },
        { name: 'Retaliate 2', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Everyday training.' },
        { name: 'LQDCELL Method', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Agility training shoe.' },
        { name: 'Velocity Nitro', image: 'https://images.unsplash.com/photo-1552346154-21d32810baa3?w=800&q=80', desc: 'Responsive trainer.' },
        { name: 'Cell Surin 2', image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', desc: 'Classic running style.' },
        { name: 'Tazon 6', image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', desc: 'Sleek cross-trainer.' },
        { name: 'Axelion', image: 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', desc: 'Breathable workout shoe.' },
      ],
      Tracksuits: [
        { name: 'T7 Track Jacket', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Iconic retro jacket.' },
        { name: 'Classic Tricot Suit', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Full tracksuit.' },
        { name: 'Evostripe Hoodie', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Articulated fit.' },
        { name: 'Active Woven Pants', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Lightweight training pants.' },
        { name: 'Power Colorblock', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Bold training jacket.' },
        { name: 'Liga Training', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Soccer training top.' },
        { name: 'Rebel Bold Suit', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Comfortable fleece suit.' },
        { name: 'Train Cloudspun', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Ultra-soft hoodie.' },
        { name: 'Motorsport Track', image: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', desc: 'Racing inspired.' },
        { name: 'PUMA Fit Woven', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', desc: 'Stretch training jacket.' },
      ]
    }
  }
};

let output = "<?php\n\nreturn [\n";
let globalId = 1;

for (const [brandKey, brandData] of Object.entries(brands)) {
  const brandName = brandData.name || brandKey.replace('_', ' ');
  for (const [catKey, products] of Object.entries(brandData.categories)) {
    const categoryName = catKey.replace('_', ' ');
    for (const prod of products) {
      let price = Math.floor(Math.random() * (brandKey === 'Gucci' || brandKey === 'Louis_Vuitton' ? 1000 : 150) + (brandKey === 'Gucci' || brandKey === 'Louis_Vuitton' ? 500 : 50));
      
      const catLower = categoryName.toLowerCase();
      let term = 'fashion';
      if (catLower.includes('bag')) term = 'bag';
      if (catLower.includes('sneaker') || catLower.includes('shoe')) term = 'sneaker';
      if (catLower.includes('hoodie')) term = 'hoodie';
      const imageUrl = `https://loremflickr.com/800/800/${term},clothing?lock=${Date.now() + globalId++}`;

      output += `    [
        'category' => '${categoryName}',
        'brand' => '${brandName}',
        'brand_logo' => '${brandData.logo}',
        'name' => '${prod.name.replace(/'/g, "\\'")}',
        'description' => '${prod.desc.replace(/'/g, "\\'")}',
        'price' => ${price}.00,
        'image_url' => '${imageUrl}',
        'sizes' => ['S', 'M', 'L'],
        'colors' => [],
    ],\n`;
    }
  }
}

output += "];\n";

fs.mkdirSync(path.join(__dirname, '../database/data'), { recursive: true });
fs.writeFileSync(path.join(__dirname, '../database/data/products.php'), output);
console.log("Successfully generated products.php with 100 products.");
