<?php

namespace Database\Seeders;

use App\Models\Biomarker;
use App\Models\Compound;
use App\Models\CompoundBiomarker;
use App\Models\CompoundDiseaseAssociation;
use App\Models\CompoundOntology;
use App\Models\Disease;
use App\Models\Ontology;
use App\Models\Source;
use Illuminate\Database\Seeder;

class CompoundSeeder extends Seeder
{
    public function run(): void
    {
        $pubchem = Source::firstOrCreate(['name' => 'PubChem']);
        $hmdb    = Source::firstOrCreate(['name' => 'HMDB']);
        $chebi   = Source::firstOrCreate(['name' => 'ChEBI']);
        $nist    = Source::firstOrCreate(['name' => 'NIST']);

        $compounds = [

            // ─── Limonene ────────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'Limonene',
                    'iupac_name'        => '(4R)-1-methyl-4-prop-1-en-2-ylcyclohexene',
                    'molecular_formula' => 'C10H16',
                    'smiles'            => 'CC(=C)[C@@H]1CCC(C)=CC1',
                    'inchi'             => 'InChI=1S/C10H16/c1-8(2)10-6-4-9(3)5-7-10/h4,10H,1,5-7H2,2-3H3/t10-/m0/s1',
                    'inchikey'          => 'XMGQYMWWDOXHJM-JTQLQIEISA-N',
                    'pubchem_cid'       => '440917',
                    'cas'               => '5989-27-5',
                    'hmdb_id'           => 'HMDB0003375',
                    'chebi_id'          => 'CHEBI:15384',
                    'description'       => 'D-Limonene is a naturally occurring monocyclic monoterpene found in the peel of citrus fruits such as oranges, lemons, and grapefruits. It is one of the most common terpenes in nature and is widely used as a flavouring and fragrance agent. D-Limonene has been studied for its potential chemopreventive properties and its ability to induce detoxification enzymes in the liver.',
                ],
                'synonyms' => [
                    'D-Limonene', '(+)-Limonene', 'Dipentene', '(R)-Limonene',
                    'Carvene', 'p-Mentha-1,8-diene', '4-Isopropenyl-1-methylcyclohexene',
                ],
                'synonyms_source' => ['PubChem', 'PubChem', 'PubChem', 'PubChem', 'HMDB', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Lipids and lipid-like molecules',
                    'class'               => 'Prenol lipids',
                    'subclass'            => 'Monoterpenoids',
                    'direct_parent'       => 'Menthane monoterpenoids',
                    'alternative_parents' => 'Monocyclic monoterpenoids; Cyclic terpenes; Branched unsaturated hydrocarbons; Cycloalkenes',
                ],
                'ri' => [
                    ['value' => 1030.0, 'column_type' => 'DB-5',      'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 1205.0, 'column_type' => 'HP-INNOWAX','is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases'   => [],
                'ontologies' => [
                    ['name' => 'Flavouring agent',  'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Plant metabolite',  'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Food metabolite',   'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'Urine dietary exposure biomarker', 'description' => 'Detectable in urine after citrus consumption'],
                ],
            ],

            // ─── Alpha-Pinene ─────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'Alpha-Pinene',
                    'iupac_name'        => '2,6,6-trimethylbicyclo[3.1.1]hept-2-ene',
                    'molecular_formula' => 'C10H16',
                    'smiles'            => 'CC1=CCC2CC1C2(C)C',
                    'inchi'             => 'InChI=1S/C10H16/c1-7-4-5-8-6-9(7)10(8,2)3/h4,8-9H,5-6H2,1-3H3',
                    'inchikey'          => 'GRWFGVWFFZKLTI-UHFFFAOYSA-N',
                    'pubchem_cid'       => '6654',
                    'cas'               => '80-56-8',
                    'hmdb_id'           => 'HMDB0031327',
                    'chebi_id'          => 'CHEBI:17797',
                    'description'       => 'Alpha-Pinene is a bicyclic monoterpenoid and the most widely encountered terpene in nature. It is a major component of pine resin, rosemary, and many other conifer species. Alpha-pinene is both an insect repellent and anti-inflammatory agent. It has been shown to act as a bronchodilator and to aid memory retention by inhibiting acetylcholinesterase activity.',
                ],
                'synonyms' => [
                    'α-Pinene', '2-Pinene', 'Pin-2(10)-ene', 'Pinene',
                    '2,6,6-Trimethylbicyclo[3.1.1]hept-2-ene',
                ],
                'synonyms_source' => ['PubChem', 'PubChem', 'PubChem', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Lipids and lipid-like molecules',
                    'class'               => 'Prenol lipids',
                    'subclass'            => 'Monoterpenoids',
                    'direct_parent'       => 'Bicyclic monoterpenoids',
                    'alternative_parents' => 'Terpenoids; Hydrocarbons; Cycloalkenes; Pinane monoterpenoids',
                ],
                'ri' => [
                    ['value' =>  939.0, 'column_type' => 'DB-5',       'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 1013.0, 'column_type' => 'HP-FFAP',    'is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases'   => [],
                'ontologies' => [
                    ['name' => 'Plant metabolite',      'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Flavouring agent',      'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Antimicrobial agent',   'type' => 'ChEBI role',   'source' => 'ChEBI'],
                ],
                'biomarkers' => [],
            ],

            // ─── β-Caryophyllene ─────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'β-Caryophyllene',
                    'iupac_name'        => '(1R,9S,E)-4,11,11-trimethyl-8-methylidenebicyclo[7.2.0]undec-4-ene',
                    'molecular_formula' => 'C15H24',
                    'smiles'            => 'C/C/1=C\CCC(=C)[C@H]2CC([C@@H]2CC1)(C)C',
                    'inchi'             => 'InChI=1S/C15H24/c1-11-6-5-7-12(2)13-10-15(3,4)14(13)9-8-11/h6,13-14H,2,5,7-10H2,1,3-4H3/b11-6+/t13-,14-/m1/s1',
                    'inchikey'          => 'NPNUFJAVOOONJE-GFUGXAQUSA-N',
                    'pubchem_cid'       => '5281515',
                    'cas'               => '87-44-5',
                    'hmdb_id'           => 'HMDB0032455',
                    'chebi_id'          => 'CHEBI:10357',
                    'description'       => 'Beta-Caryophyllene is a bicyclic sesquiterpene that occurs naturally in many essential oils, most notably in cannabis, black pepper, and cloves. It is the most common sesquiterpene hydrocarbon in nature. Beta-caryophyllene is a selective agonist of the CB2 cannabinoid receptor and has demonstrated anti-inflammatory, analgesic, and antioxidant effects. It is approved as a food additive by the FDA.',
                ],
                'synonyms' => [
                    'β-Caryophyllene', 'Caryophyllene', '(-)-Caryophyllene',
                    'trans-Caryophyllene', 'β-Kariofilen',
                ],
                'synonyms_source' => ['PubChem', 'PubChem', 'PubChem', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Lipids and lipid-like molecules',
                    'class'               => 'Prenol lipids',
                    'subclass'            => 'Sesquiterpenoids',
                    'direct_parent'       => 'Caryophyllane sesquiterpenoids',
                    'alternative_parents' => 'Terpenoids; Hydrocarbons; Bicyclic compounds; Cycloalkenes',
                ],
                'ri' => [
                    ['value' => 1418.0, 'column_type' => 'DB-5',       'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 1596.0, 'column_type' => 'HP-INNOWAX', 'is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases'   => [],
                'ontologies' => [
                    ['name' => 'Plant metabolite',       'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Anti-inflammatory agent','type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Cannabinoid receptor agonist', 'type' => 'ChEBI role', 'source' => 'ChEBI'],
                    ['name' => 'Food metabolite',        'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [],
            ],

            // ─── D-Glucose ───────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'D-Glucose',
                    'iupac_name'        => '(3R,4S,5S,6R)-6-(hydroxymethyl)oxane-2,3,4,5-tetrol',
                    'molecular_formula' => 'C6H12O6',
                    'smiles'            => 'C([C@@H]1[C@H]([C@@H]([C@H](C(O1)O)O)O)O)O',
                    'inchi'             => 'InChI=1S/C6H12O6/c7-1-2-3(8)4(9)5(10)6(11)12-2/h2-11H,1H2/t2-,3-,4+,5-,6?/m1/s1',
                    'inchikey'          => 'WQZGKKKJIJFFOK-GASJEMHNSA-N',
                    'pubchem_cid'       => '5793',
                    'cas'               => '50-99-7',
                    'hmdb_id'           => 'HMDB0000122',
                    'chebi_id'          => 'CHEBI:4167',
                    'description'       => 'D-Glucose (also called dextrose) is a monosaccharide and the primary energy source for cellular metabolism. It is the most abundant sugar in the blood of most animals and is a fundamental substrate in glycolysis, the Krebs cycle, and oxidative phosphorylation. Blood glucose levels are tightly regulated by insulin and glucagon. Abnormal glucose metabolism is central to diabetes mellitus, and elevated serum glucose is a widely used biomarker for this condition.',
                ],
                'synonyms' => [
                    'Glucose', 'Dextrose', 'D-Glucopyranose', 'Blood sugar',
                    'Corn sugar', 'Grape sugar', 'D-Glucose anhydrous',
                ],
                'synonyms_source' => ['PubChem', 'PubChem', 'PubChem', 'HMDB', 'HMDB', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Organic oxygen compounds',
                    'class'               => 'Organooxygen compounds',
                    'subclass'            => 'Carbohydrates and carbohydrate conjugates',
                    'direct_parent'       => 'Hexoses',
                    'alternative_parents' => 'Monosaccharides; Aldohexoses; Pyranoses; Oxanes; Polyols',
                ],
                'ri' => [],
                'diseases' => [
                    ['name' => 'Diabetes mellitus type 1',  'category' => 'Endocrine disorder'],
                    ['name' => 'Diabetes mellitus type 2',  'category' => 'Endocrine disorder'],
                    ['name' => 'Hypoglycemia',              'category' => 'Endocrine disorder'],
                    ['name' => 'Galactosemia',              'category' => 'Inborn error of metabolism'],
                    ['name' => 'Glycogen storage disease',  'category' => 'Inborn error of metabolism'],
                    ['name' => 'Metabolic syndrome',        'category' => 'Metabolic disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Metabolite',            'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Food component',        'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Fuel molecule',         'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',      'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Glycolysis substrate',  'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'Blood glucose biomarker',    'description' => 'Elevated blood glucose (≥7.0 mmol/L fasting) is diagnostic for diabetes mellitus'],
                    ['name' => 'Urine glucose biomarker',    'description' => 'Glucosuria occurs when plasma glucose exceeds the renal threshold (~10 mmol/L)'],
                ],
            ],

            // ─── D-Fructose ──────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'D-Fructose',
                    'iupac_name'        => '(3S,4R,5R)-1,3,4,5,6-pentahydroxyhexan-2-one',
                    'molecular_formula' => 'C6H12O6',
                    'smiles'            => 'C([C@H]([C@H]([C@@H](C(=O)CO)O)O)O)O',
                    'inchi'             => 'InChI=1S/C6H12O6/c7-1-3(9)5(11)6(12)4(10)2-8/h3,5-9,11-12H,1-2H2/t3-,5-,6-/m1/s1',
                    'inchikey'          => 'BJHIKXHVCXFQLS-UYFOZJQFSA-N',
                    'pubchem_cid'       => '5984',
                    'cas'               => '57-48-7',
                    'hmdb_id'           => 'HMDB0000660',
                    'chebi_id'          => 'CHEBI:15824',
                    'description'       => 'D-Fructose is a monosaccharide ketose found abundantly in fruits, honey, and root vegetables. It is also a component of sucrose and high-fructose corn syrup. Unlike glucose, fructose metabolism bypasses key regulatory steps in glycolysis and is primarily metabolised in the liver. Excessive fructose consumption has been linked to non-alcoholic fatty liver disease, insulin resistance, hypertriglyceridaemia, and visceral obesity.',
                ],
                'synonyms' => [
                    'Fructose', 'Fruit sugar', 'Levulose', 'D-Fructopyranose',
                    'Laevulose', 'D-Arabino-2-hexulose',
                ],
                'synonyms_source' => ['PubChem', 'HMDB', 'PubChem', 'PubChem', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Organic oxygen compounds',
                    'class'               => 'Organooxygen compounds',
                    'subclass'            => 'Carbohydrates and carbohydrate conjugates',
                    'direct_parent'       => 'Ketohexoses',
                    'alternative_parents' => 'Monosaccharides; Ketoses; Hexoses; Furanoses; Polyols',
                ],
                'ri' => [],
                'diseases' => [
                    ['name' => 'Hereditary fructose intolerance', 'category' => 'Inborn error of metabolism'],
                    ['name' => 'Fructosuria',                     'category' => 'Inborn error of metabolism'],
                    ['name' => 'Non-alcoholic fatty liver disease','category' => 'Metabolic disorder'],
                    ['name' => 'Metabolic syndrome',              'category' => 'Metabolic disorder'],
                    ['name' => 'Diabetes mellitus type 2',        'category' => 'Endocrine disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Metabolite',         'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Food component',     'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Human metabolite',   'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Sweetening agent',   'type' => 'ChEBI role',   'source' => 'ChEBI'],
                ],
                'biomarkers' => [
                    ['name' => 'Urine fructose biomarker', 'description' => 'Elevated urinary fructose occurs in essential fructosuria and hereditary fructose intolerance'],
                ],
            ],

            // ─── Dopamine ────────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'Dopamine',
                    'iupac_name'        => '4-(2-aminoethyl)benzene-1,2-diol',
                    'molecular_formula' => 'C8H11NO2',
                    'smiles'            => 'NCCc1ccc(O)c(O)c1',
                    'inchi'             => 'InChI=1S/C8H11NO2/c9-4-3-6-1-2-7(10)8(11)5-6/h1-2,5,10-11H,3-4,9H2',
                    'inchikey'          => 'VYFYYTLLBUKUHU-UHFFFAOYSA-N',
                    'pubchem_cid'       => '681',
                    'cas'               => '51-61-6',
                    'hmdb_id'           => 'HMDB0000073',
                    'chebi_id'          => 'CHEBI:18243',
                    'description'       => 'Dopamine is a catecholamine neurotransmitter and neuromodulator that plays a key role in reward-motivated behaviour, motor control, and the regulation of prolactin release. It is synthesised from L-tyrosine via L-DOPA in dopaminergic neurons and the adrenal medulla. Depletion of dopamine in the nigrostriatal pathway is the cardinal biochemical feature of Parkinson\'s disease. Dopamine dysregulation is also implicated in schizophrenia, addiction, ADHD, and major depressive disorder.',
                ],
                'synonyms' => [
                    '3-Hydroxytyramine', 'DA', '4-(2-Aminoethyl)catechol',
                    'Oxytyramine', '3,4-Dihydroxyphenethylamine',
                    'Intropin', 'Hydroxytyramine',
                ],
                'synonyms_source' => ['PubChem', 'HMDB', 'PubChem', 'HMDB', 'PubChem', 'HMDB', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Benzenoids',
                    'class'               => 'Phenols',
                    'subclass'            => 'Catechols',
                    'direct_parent'       => 'Catecholamines',
                    'alternative_parents' => 'Phenethylamines; Aromatic amines; 1,2-diols; Neurotransmitters; Monoamine oxidase substrates',
                ],
                'ri' => [],
                'diseases' => [
                    ['name' => 'Parkinson\'s disease',                  'category' => 'Neurological disorder'],
                    ['name' => 'Schizophrenia',                         'category' => 'Psychiatric disorder'],
                    ['name' => 'Major depressive disorder',             'category' => 'Psychiatric disorder'],
                    ['name' => 'Attention deficit hyperactivity disorder','category' => 'Psychiatric disorder'],
                    ['name' => 'Bipolar disorder',                      'category' => 'Psychiatric disorder'],
                    ['name' => 'Pheochromocytoma',                      'category' => 'Endocrine neoplasm'],
                ],
                'ontologies' => [
                    ['name' => 'Neurotransmitter',              'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Hormone',                       'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Catecholamine',                 'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',              'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Dopaminergic neurotransmission','type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Monoamine neurotransmitter',    'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'CSF dopamine biomarker',   'description' => 'Reduced CSF dopamine and its metabolite HVA in Parkinson\'s disease and related disorders'],
                    ['name' => 'Urine catecholamine panel', 'description' => 'Elevated urinary dopamine in pheochromocytoma, paraganglioma, and neuroblastoma'],
                ],
            ],

            // ─── L-Alanine ───────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'L-Alanine',
                    'iupac_name'        => '(2S)-2-aminopropanoic acid',
                    'molecular_formula' => 'C3H7NO2',
                    'smiles'            => 'C[C@@H](C(=O)O)N',
                    'inchi'             => 'InChI=1S/C3H7NO2/c1-2(4)3(5)6/h2H,4H2,1H3,(H,5,6)/t2-/m0/s1',
                    'inchikey'          => 'QNAYBMKLOCPYGJ-REOHCLBHSA-N',
                    'pubchem_cid'       => '5950',
                    'cas'               => '56-41-7',
                    'hmdb_id'           => 'HMDB0000161',
                    'chebi_id'          => 'CHEBI:16977',
                    'description'       => 'L-Alanine is a non-essential proteinogenic alpha-amino acid that plays a central role in glucose-alanine cycle: alanine released by skeletal muscle during fasting is taken up by the liver and converted to pyruvate via transamination, then used for gluconeogenesis. It is the most abundant non-essential amino acid in plasma and is involved in the transfer of nitrogen between tissues. L-Alanine concentrations in blood are commonly measured as part of amino acid profiles in metabolic disease screening.',
                ],
                'synonyms' => [
                    'Alanine', 'L-2-Aminopropanoic acid', 'Ala', 'L-Ala',
                    '(S)-Alanine', '(S)-2-Aminopropionic acid', 'L-α-Alanine',
                ],
                'synonyms_source' => ['HMDB', 'PubChem', 'HMDB', 'HMDB', 'PubChem', 'PubChem', 'HMDB'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Organic acids and derivatives',
                    'class'               => 'Carboxylic acids and derivatives',
                    'subclass'            => 'Amino acids, peptides, and analogues',
                    'direct_parent'       => 'Alpha amino acids',
                    'alternative_parents' => 'Amino acids; Carboxylic acids; Organonitrogen compounds; L-amino acids; Proteinogenic amino acids',
                ],
                'ri' => [
                    ['value' =>  907.0, 'column_type' => 'DB-5 (as TMS derivative)',   'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 1110.0, 'column_type' => 'HP-FFAP (as TMS derivative)','is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases' => [
                    ['name' => 'Hyper-beta-alaninemia',      'category' => 'Inborn error of metabolism'],
                    ['name' => 'Pyruvate kinase deficiency',  'category' => 'Inborn error of metabolism'],
                    ['name' => 'Liver disease',               'category' => 'Hepatic disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Proteinogenic amino acid',    'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',            'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Gluconeogenesis substrate',   'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Glucose-alanine cycle',       'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Urea cycle',                  'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'Plasma amino acid biomarker', 'description' => 'Altered plasma alanine levels observed in metabolic syndrome, type 2 diabetes, and liver disease'],
                ],
            ],

            // ─── L-Glutamic acid ─────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'L-Glutamic acid',
                    'iupac_name'        => '(2S)-2-aminopentanedioic acid',
                    'molecular_formula' => 'C5H9NO4',
                    'smiles'            => 'N[C@@H](CCC(=O)O)C(=O)O',
                    'inchi'             => 'InChI=1S/C5H9NO4/c6-3(5(9)10)1-2-4(7)8/h3H,1-2,6H2,(H,7,8)(H,9,10)/t3-/m0/s1',
                    'inchikey'          => 'WHUUTDBJXJRKMK-VKHMYHEASA-N',
                    'pubchem_cid'       => '33032',
                    'cas'               => '56-86-0',
                    'hmdb_id'           => 'HMDB0000148',
                    'chebi_id'          => 'CHEBI:16015',
                    'description'       => 'L-Glutamic acid is a non-essential proteinogenic amino acid and the predominant excitatory neurotransmitter in the mammalian central nervous system. It is the precursor to GABA (via glutamate decarboxylase) and plays a central role in nitrogen metabolism through transamination reactions and the urea cycle. Glutamate receptors (AMPA, NMDA, kainate) mediate fast excitatory synaptic transmission. Excitotoxicity from excess synaptic glutamate is implicated in stroke, ALS, Alzheimer\'s disease, and traumatic brain injury.',
                ],
                'synonyms' => [
                    'Glutamic acid', 'L-Glutamate', 'Glutamate',
                    'L-Glu', 'Glu', '(S)-Glutamic acid',
                    '2-Aminopentanedioic acid',
                ],
                'synonyms_source' => ['HMDB', 'HMDB', 'HMDB', 'HMDB', 'HMDB', 'PubChem', 'PubChem'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Organic acids and derivatives',
                    'class'               => 'Carboxylic acids and derivatives',
                    'subclass'            => 'Amino acids, peptides, and analogues',
                    'direct_parent'       => 'Glutamic acid and derivatives',
                    'alternative_parents' => 'Alpha amino acids; Dicarboxylic acids; L-amino acids; Neurotransmitters; Proteinogenic amino acids',
                ],
                'ri' => [],
                'diseases' => [
                    ['name' => 'Amyotrophic lateral sclerosis',      'category' => 'Neurological disorder'],
                    ['name' => 'Maple syrup urine disease',           'category' => 'Inborn error of metabolism'],
                    ['name' => 'Pyroglutamic aciduria',               'category' => 'Inborn error of metabolism'],
                    ['name' => 'Alzheimer\'s disease',                'category' => 'Neurological disorder'],
                    ['name' => 'Ischemic stroke',                     'category' => 'Cerebrovascular disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Excitatory neurotransmitter',   'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Proteinogenic amino acid',      'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',              'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Urea cycle',                    'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Glutamate metabolism',          'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'GABA synthesis substrate',      'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'CSF glutamate biomarker',   'description' => 'Elevated CSF glutamate is associated with ALS, Alzheimer\'s disease, and ischaemic stroke'],
                    ['name' => 'Plasma amino acid biomarker','description' => 'Altered plasma glutamate in inborn errors of amino acid metabolism and liver disease'],
                ],
            ],

            // ─── Palmitic acid ───────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'Palmitic acid',
                    'iupac_name'        => 'hexadecanoic acid',
                    'molecular_formula' => 'C16H32O2',
                    'smiles'            => 'CCCCCCCCCCCCCCCC(=O)O',
                    'inchi'             => 'InChI=1S/C16H32O2/c1-2-3-4-5-6-7-8-9-10-11-12-13-14-15-16(17)18/h2-15H2,1H3,(H,17,18)',
                    'inchikey'          => 'IPCSVZSSVZVIGE-UHFFFAOYSA-N',
                    'pubchem_cid'       => '985',
                    'cas'               => '57-10-3',
                    'hmdb_id'           => 'HMDB0000220',
                    'chebi_id'          => 'CHEBI:15756',
                    'description'       => 'Palmitic acid is the most common saturated fatty acid found in animals, plants, and micro-organisms. It constitutes 20–30% of total fatty acids in human adipose tissue and is a major component of palm oil and dairy fats. In metabolism, palmitic acid is the primary product of de novo fatty acid synthesis (by fatty acid synthase) and serves as the precursor for longer-chain saturated and unsaturated fatty acids. High dietary intake of palmitic acid is associated with elevated LDL cholesterol, atherosclerosis, insulin resistance, and increased cardiovascular risk.',
                ],
                'synonyms' => [
                    'Hexadecanoic acid', 'C16:0', 'Palmitate',
                    'n-Hexadecanoic acid', 'Cetylic acid', '1-Pentadecanecarboxylic acid',
                ],
                'synonyms_source' => ['PubChem', 'HMDB', 'HMDB', 'PubChem', 'HMDB', 'PubChem'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Lipids and lipid-like molecules',
                    'class'               => 'Fatty Acyls',
                    'subclass'            => 'Fatty acids and conjugates',
                    'direct_parent'       => 'Long-chain fatty acids',
                    'alternative_parents' => 'Saturated fatty acids; Straight-chain fatty acids; Monocarboxylic acids',
                ],
                'ri' => [
                    ['value' => 1976.0, 'column_type' => 'DB-5',       'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 2080.0, 'column_type' => 'HP-INNOWAX', 'is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases' => [
                    ['name' => 'Cardiovascular disease',    'category' => 'Cardiovascular disorder'],
                    ['name' => 'Atherosclerosis',           'category' => 'Cardiovascular disorder'],
                    ['name' => 'Obesity',                   'category' => 'Metabolic disorder'],
                    ['name' => 'Insulin resistance',        'category' => 'Metabolic disorder'],
                    ['name' => 'Non-alcoholic fatty liver disease', 'category' => 'Hepatic disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Saturated fatty acid',          'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',              'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Food component',                'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'De novo lipogenesis product',   'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Beta-oxidation substrate',      'type' => 'HMDB process', 'source' => 'HMDB'],
                ],
                'biomarkers' => [
                    ['name' => 'Plasma lipid biomarker',  'description' => 'Elevated plasma palmitic acid is associated with cardiovascular risk, insulin resistance, and metabolic syndrome'],
                    ['name' => 'Adipose tissue biomarker','description' => 'Palmitic acid proportion in adipose tissue reflects long-term dietary saturated fat intake'],
                ],
            ],

            // ─── Oleic acid ──────────────────────────────────────────────────────
            [
                'data' => [
                    'canonical_name'    => 'Oleic acid',
                    'iupac_name'        => '(Z)-octadec-9-enoic acid',
                    'molecular_formula' => 'C18H34O2',
                    'smiles'            => 'CCCCCCCC/C=C\CCCCCCCC(=O)O',
                    'inchi'             => 'InChI=1S/C18H34O2/c1-2-3-4-5-6-7-8-9-10-11-12-13-14-15-16-17-18(19)20/h9-10H,2-8,11-17H2,1H3,(H,19,20)/b10-9-',
                    'inchikey'          => 'ZQPPMHVWECSIRJ-KTKRTIGZSA-N',
                    'pubchem_cid'       => '445639',
                    'cas'               => '112-80-1',
                    'hmdb_id'           => 'HMDB0000207',
                    'chebi_id'          => 'CHEBI:16196',
                    'description'       => 'Oleic acid is an omega-9 monounsaturated fatty acid and the most abundant fatty acid in human adipose tissue, comprising approximately 45–55% of total fatty acids. It is the principal fatty acid in olive oil and is central to the health benefits of the Mediterranean diet. Oleic acid promotes VLDL secretion, reduces LDL oxidation, and has anti-inflammatory and cardioprotective properties. It is also a substrate for the synthesis of oleamide (a sleep-inducing lipid) and is involved in the regulation of cell membrane fluidity and permeability.',
                ],
                'synonyms' => [
                    'cis-9-Octadecenoic acid', 'C18:1(n-9)', 'Oleate',
                    '(9Z)-Octadecenoic acid', 'cis-Oleic acid',
                    'Olive oil acid', '9-Octadecenoic acid',
                ],
                'synonyms_source' => ['PubChem', 'HMDB', 'HMDB', 'PubChem', 'HMDB', 'HMDB', 'PubChem'],
                'taxonomy' => [
                    'kingdom'             => 'Organic compounds',
                    'superclass'          => 'Lipids and lipid-like molecules',
                    'class'               => 'Fatty Acyls',
                    'subclass'            => 'Fatty acids and conjugates',
                    'direct_parent'       => 'Long-chain monounsaturated fatty acids',
                    'alternative_parents' => 'Unsaturated fatty acids; Straight-chain fatty acids; Monocarboxylic acids; Omega-9 fatty acids',
                ],
                'ri' => [
                    ['value' => 2154.0, 'column_type' => 'DB-5',       'is_polar' => false, 'source' => 'NIST'],
                    ['value' => 2318.0, 'column_type' => 'HP-INNOWAX', 'is_polar' => true,  'source' => 'NIST'],
                ],
                'diseases' => [
                    ['name' => 'Cardiovascular disease', 'category' => 'Cardiovascular disorder'],
                    ['name' => 'Obesity',                'category' => 'Metabolic disorder'],
                    ['name' => 'Metabolic syndrome',     'category' => 'Metabolic disorder'],
                    ['name' => 'Hypertension',           'category' => 'Cardiovascular disorder'],
                ],
                'ontologies' => [
                    ['name' => 'Monounsaturated fatty acid', 'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Human metabolite',           'type' => 'ChEBI role',   'source' => 'ChEBI'],
                    ['name' => 'Food component',             'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Beta-oxidation substrate',   'type' => 'HMDB process', 'source' => 'HMDB'],
                    ['name' => 'Anti-inflammatory agent',    'type' => 'ChEBI role',   'source' => 'ChEBI'],
                ],
                'biomarkers' => [
                    ['name' => 'Plasma lipid biomarker',    'description' => 'Plasma oleic acid reflects Mediterranean diet adherence and correlates inversely with cardiovascular risk'],
                    ['name' => 'Erythrocyte membrane fatty acid biomarker', 'description' => 'Red blood cell oleic acid proportion is a marker of long-term dietary monounsaturated fat intake'],
                ],
            ],

        ]; // end $compounds

        // ─── Seed loop ───────────────────────────────────────────────────────────

        $sourceMap = [
            'PubChem' => $pubchem,
            'HMDB'    => $hmdb,
            'ChEBI'   => $chebi,
            'NIST'    => $nist,
        ];

        foreach ($compounds as $item) {
            $d = $item['data'];
            $compound = Compound::where('inchikey', $d['inchikey'])
                ->orWhere('inchi', $d['inchi'])
                ->when(!empty($d['hmdb_id']),    fn($q) => $q->orWhere('hmdb_id',    $d['hmdb_id']))
                ->when(!empty($d['chebi_id']),   fn($q) => $q->orWhere('chebi_id',   $d['chebi_id']))
                ->when(!empty($d['pubchem_cid']),fn($q) => $q->orWhere('pubchem_cid',$d['pubchem_cid']))
                ->when(!empty($d['cas']),        fn($q) => $q->orWhere('cas',        $d['cas']))
                ->first();

            if ($compound) {
                $compound->update($d);
            } else {
                $compound = Compound::create($d);
            }

            // Synonyms
            foreach ($item['synonyms'] as $i => $synName) {
                $srcKey    = $item['synonyms_source'][$i] ?? 'PubChem';
                $srcModel  = $sourceMap[$srcKey] ?? $pubchem;
                $compound->synonyms()->updateOrCreate(
                    ['compound_id' => $compound->id, 'name' => $synName],
                    ['source_id'   => $srcModel->id]
                );
            }

            // Taxonomy
            $compound->taxonomy()->updateOrCreate(
                ['compound_id' => $compound->id],
                array_merge($item['taxonomy'], ['raw_json' => json_encode($item['taxonomy'])])
            );

            // Retention indices
            foreach ($item['ri'] as $ri) {
                $riSource = $sourceMap[$ri['source']] ?? $nist;
                $compound->retentionIndices()->updateOrCreate(
                    [
                        'compound_id' => $compound->id,
                        'column_type' => $ri['column_type'],
                        'is_polar'    => $ri['is_polar'],
                    ],
                    [
                        'value'     => $ri['value'],
                        'source_id' => $riSource->id,
                        'reference' => 'NIST Chemistry WebBook',
                    ]
                );
            }

            // Diseases
            foreach ($item['diseases'] as $d) {
                $disease = Disease::firstOrCreate(
                    ['name' => $d['name']],
                    ['category' => $d['category'] ?? null]
                );
                CompoundDiseaseAssociation::firstOrCreate([
                    'compound_id' => $compound->id,
                    'disease_id'  => $disease->id,
                    'source_id'   => $hmdb->id,
                ]);
            }

            // Ontologies
            foreach ($item['ontologies'] as $o) {
                $srcModel = $sourceMap[$o['source']] ?? $hmdb;
                $ontology = Ontology::firstOrCreate(
                    ['name' => $o['name']],
                    ['type' => $o['type'] ?? null]
                );
                CompoundOntology::firstOrCreate([
                    'compound_id' => $compound->id,
                    'ontology_id' => $ontology->id,
                    'source_id'   => $srcModel->id,
                ]);
            }

            // Biomarkers
            foreach ($item['biomarkers'] as $b) {
                $biomarker = Biomarker::firstOrCreate(
                    ['name' => $b['name']],
                    ['description' => $b['description'] ?? null]
                );
                CompoundBiomarker::firstOrCreate([
                    'compound_id'  => $compound->id,
                    'biomarker_id' => $biomarker->id,
                    'source_id'    => $hmdb->id,
                ]);
            }
        }
    }
}
