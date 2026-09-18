using System.Globalization;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// MaxiCode (ISO/IEC 16023). ZXing에 인코더가 없고 모듈이 정사각형이 아니라 정육각형 벌집이라
/// BitMatrix 경로에는 태울 수 없다. 33행 × 30열 육각 격자만 만들고
/// 가운데 과녁(동심원 셋)은 BarcodeRenderer가 따로 그린다.
/// 「QR코드 타입.lbl」 7번 칸 실측: 모드 5, 값 "MaxiCode".
/// </summary>
internal static class MaxiCodeEncoder
{
    public const int Rows = 33;

    public const int Columns = 30;

    /// <summary>모드 4·6은 SEC(자료 84 + 오류정정 40), 모드 5는 EEC(자료 68 + 오류정정 56)다.</summary>
    private const int SecondaryDataSec = 84;

    private const int SecondaryDataEec = 68;

    /// <summary>1차 메시지는 모드와 무관하게 자료 10 + 오류정정 10으로 고정이다(4.10.2).</summary>
    private const int PrimaryData = 10;

    private const int CodewordCount = 144;

    /// <summary>GF(64) 생성 다항식 x⁶+x+1(4.10.1).</summary>
    private const int FieldPolynomial = 0x43;

    private const int FieldSize = 63;

    // 부호집합 상태. PAD가 있는 A·B·E를 먼저 두어 최단 경로가 안정되게 한다.
    private const int StateA = 0;
    private const int StateB = 1;
    private const int StateE = 2;
    private const int StateC = 3;
    private const int StateD = 4;
    private const int StateCount = 5;

    // 연산 표식. 하위 5비트는 목표 부호집합, 0x20은 한 글자 시프트다.
    private const int OpDigits = 0;
    private const int OpSetA = 0x01;
    private const int OpSetB = 0x02;
    private const int OpSetE = 0x04;
    private const int OpSetC = 0x08;
    private const int OpSetD = 0x10;
    private const int OpShiftA = 0x20 | OpSetA;
    private const int OpDoubleShiftA = 0x40 | OpSetA;
    private const int OpTripleShiftA = 0x80 | OpSetA;
    private const int OpShiftB = 0x20 | OpSetB;
    private const int OpShiftE = 0x20 | OpSetE;
    private const int OpShiftC = 0x20 | OpSetC;
    private const int OpShiftD = 0x20 | OpSetD;

    /// <summary><see cref="OpCodes"/>에서 이 번째부터는 시프트라 부호어가 하나 더 붙는다.</summary>
    private const int ShiftOpIndex = 6;

    private const byte NumericShift = 31;
    private const byte PadA = 33;
    private const byte PadE = 28;
    private const byte LatchToA = 58;

    /// <summary>어느 상태에서도 못 담을 때 쓰는 큰 값. 최단 경로 비교에서 항상 밀린다.</summary>
    private const int Unreachable = 999999;

    /// <summary>
    /// ISO/IEC 16023 그림 5. 값 n은 n번째 자료 비트가 놓이는 육각형 자리다.
    /// 0은 자료를 담지 않는 자리(과녁·방향무늬·오른쪽 끝 빈칸)다.
    /// </summary>
    private static readonly ushort[] ModuleSequence =
    [
        122, 121, 128, 127, 134, 133, 140, 139, 146, 145, 152, 151, 158, 157, 164, 163, 170, 169, 176, 175, 182, 181, 188, 187, 194, 193, 200, 199,   0,   0,
        124, 123, 130, 129, 136, 135, 142, 141, 148, 147, 154, 153, 160, 159, 166, 165, 172, 171, 178, 177, 184, 183, 190, 189, 196, 195, 202, 201, 817,   0,
        126, 125, 132, 131, 138, 137, 144, 143, 150, 149, 156, 155, 162, 161, 168, 167, 174, 173, 180, 179, 186, 185, 192, 191, 198, 197, 204, 203, 819, 818,
        284, 283, 278, 277, 272, 271, 266, 265, 260, 259, 254, 253, 248, 247, 242, 241, 236, 235, 230, 229, 224, 223, 218, 217, 212, 211, 206, 205, 820,   0,
        286, 285, 280, 279, 274, 273, 268, 267, 262, 261, 256, 255, 250, 249, 244, 243, 238, 237, 232, 231, 226, 225, 220, 219, 214, 213, 208, 207, 822, 821,
        288, 287, 282, 281, 276, 275, 270, 269, 264, 263, 258, 257, 252, 251, 246, 245, 240, 239, 234, 233, 228, 227, 222, 221, 216, 215, 210, 209, 823,   0,
        290, 289, 296, 295, 302, 301, 308, 307, 314, 313, 320, 319, 326, 325, 332, 331, 338, 337, 344, 343, 350, 349, 356, 355, 362, 361, 368, 367, 825, 824,
        292, 291, 298, 297, 304, 303, 310, 309, 316, 315, 322, 321, 328, 327, 334, 333, 340, 339, 346, 345, 352, 351, 358, 357, 364, 363, 370, 369, 826,   0,
        294, 293, 300, 299, 306, 305, 312, 311, 318, 317, 324, 323, 330, 329, 336, 335, 342, 341, 348, 347, 354, 353, 360, 359, 366, 365, 372, 371, 828, 827,
        410, 409, 404, 403, 398, 397, 392, 391,  80,  79,   0,   0,  14,  13,  38,  37,   3,   0,  45,  44, 110, 109, 386, 385, 380, 379, 374, 373, 829,   0,
        412, 411, 406, 405, 400, 399, 394, 393,  82,  81,  41,   0,  16,  15,  40,  39,   4,   0,   0,  46, 112, 111, 388, 387, 382, 381, 376, 375, 831, 830,
        414, 413, 408, 407, 402, 401, 396, 395,  84,  83,  42,   0,   0,   0,   0,   0,   6,   5,  48,  47, 114, 113, 390, 389, 384, 383, 378, 377, 832,   0,
        416, 415, 422, 421, 428, 427, 104, 103,  56,  55,  17,   0,   0,   0,   0,   0,   0,   0,  21,  20,  86,  85, 434, 433, 440, 439, 446, 445, 834, 833,
        418, 417, 424, 423, 430, 429, 106, 105,  58,  57,   0,   0,   0,   0,   0,   0,   0,   0,  23,  22,  88,  87, 436, 435, 442, 441, 448, 447, 835,   0,
        420, 419, 426, 425, 432, 431, 108, 107,  60,  59,   0,   0,   0,   0,   0,   0,   0,   0,   0,  24,  90,  89, 438, 437, 444, 443, 450, 449, 837, 836,
        482, 481, 476, 475, 470, 469,  49,   0,  31,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   1,  54,  53, 464, 463, 458, 457, 452, 451, 838,   0,
        484, 483, 478, 477, 472, 471,  50,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0, 466, 465, 460, 459, 454, 453, 840, 839,
        486, 485, 480, 479, 474, 473,  52,  51,  32,   0,   0,   0,   0,   0,   0,   0,   0,   0,   0,   2,   0,  43, 468, 467, 462, 461, 456, 455, 841,   0,
        488, 487, 494, 493, 500, 499,  98,  97,  62,  61,   0,   0,   0,   0,   0,   0,   0,   0,   0,  27,  92,  91, 506, 505, 512, 511, 518, 517, 843, 842,
        490, 489, 496, 495, 502, 501, 100,  99,  64,  63,   0,   0,   0,   0,   0,   0,   0,   0,  29,  28,  94,  93, 508, 507, 514, 513, 520, 519, 844,   0,
        492, 491, 498, 497, 504, 503, 102, 101,  66,  65,  18,   0,   0,   0,   0,   0,   0,   0,  19,  30,  96,  95, 510, 509, 516, 515, 522, 521, 846, 845,
        560, 559, 554, 553, 548, 547, 542, 541,  74,  73,  33,   0,   0,   0,   0,   0,   0,  11,  68,  67, 116, 115, 536, 535, 530, 529, 524, 523, 847,   0,
        562, 561, 556, 555, 550, 549, 544, 543,  76,  75,   0,   0,   8,   7,  36,  35,  12,   0,  70,  69, 118, 117, 538, 537, 532, 531, 526, 525, 849, 848,
        564, 563, 558, 557, 552, 551, 546, 545,  78,  77,   0,  34,  10,   9,  26,  25,   0,   0,  72,  71, 120, 119, 540, 539, 534, 533, 528, 527, 850,   0,
        566, 565, 572, 571, 578, 577, 584, 583, 590, 589, 596, 595, 602, 601, 608, 607, 614, 613, 620, 619, 626, 625, 632, 631, 638, 637, 644, 643, 852, 851,
        568, 567, 574, 573, 580, 579, 586, 585, 592, 591, 598, 597, 604, 603, 610, 609, 616, 615, 622, 621, 628, 627, 634, 633, 640, 639, 646, 645, 853,   0,
        570, 569, 576, 575, 582, 581, 588, 587, 594, 593, 600, 599, 606, 605, 612, 611, 618, 617, 624, 623, 630, 629, 636, 635, 642, 641, 648, 647, 855, 854,
        728, 727, 722, 721, 716, 715, 710, 709, 704, 703, 698, 697, 692, 691, 686, 685, 680, 679, 674, 673, 668, 667, 662, 661, 656, 655, 650, 649, 856,   0,
        730, 729, 724, 723, 718, 717, 712, 711, 706, 705, 700, 699, 694, 693, 688, 687, 682, 681, 676, 675, 670, 669, 664, 663, 658, 657, 652, 651, 858, 857,
        732, 731, 726, 725, 720, 719, 714, 713, 708, 707, 702, 701, 696, 695, 690, 689, 684, 683, 678, 677, 672, 671, 666, 665, 660, 659, 654, 653, 859,   0,
        734, 733, 740, 739, 746, 745, 752, 751, 758, 757, 764, 763, 770, 769, 776, 775, 782, 781, 788, 787, 794, 793, 800, 799, 806, 805, 812, 811, 861, 860,
        736, 735, 742, 741, 748, 747, 754, 753, 760, 759, 766, 765, 772, 771, 778, 777, 784, 783, 790, 789, 796, 795, 802, 801, 808, 807, 814, 813, 862,   0,
        738, 737, 744, 743, 750, 749, 756, 755, 762, 761, 768, 767, 774, 773, 780, 779, 786, 785, 792, 791, 798, 797, 804, 803, 810, 809, 816, 815, 864, 863
    ];

    /// <summary>
    /// 4.11.3 방향 무늬. 자료를 담지 않지만 읽는 쪽이 회전을 판별하는 자리라 항상 검게 채운다.
    /// (0,28)·(0,29)는 첫 행 오른쪽 끝을 메우는 자리다.
    /// </summary>
    private static readonly (int Row, int Col)[] OrientationModules =
    [
        (0, 28), (0, 29),
        (9, 10), (9, 11), (10, 11),
        (15, 7), (16, 8),
        (16, 20), (17, 20),
        (22, 10), (23, 10),
        (22, 17), (23, 17)
    ];

    /// <summary>
    /// 부록 A. 글자를 담을 수 있는 부호집합 표식(A 0x01, B 0x02, E 0x04, C 0x08, D 0x10).
    /// 여러 집합에 걸친 글자는 표식을 OR로 겹쳐 둔다(CR은 A·E, 「FS GS RS SP」는 전부, 「,./:」는 A·B).
    /// </summary>
    private static readonly byte[] CodeSetFlags =
    [
        0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x05, 0x04, 0x04,   //   0- 15
        0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x1F, 0x1F, 0x1F, 0x04,   //  16- 31
        0x1F, 0x02, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x03, 0x01, 0x03, 0x03,   //  32- 47
        0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x03, 0x02, 0x02, 0x02, 0x02, 0x02,   //  48- 63
        0x02, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01,   //  64- 79
        0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x02, 0x02, 0x02, 0x02, 0x02,   //  80- 95
        0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02,   //  96-111
        0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x02,   // 112-127
        0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10,   // 128-143
        0x10, 0x10, 0x10, 0x10, 0x10, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04,   // 144-159
        0x04, 0x10, 0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x10, 0x04, 0x08, 0x10, 0x08, 0x04, 0x04, 0x10,   // 160-175
        0x10, 0x08, 0x08, 0x08, 0x10, 0x08, 0x04, 0x10, 0x10, 0x08, 0x08, 0x10, 0x08, 0x08, 0x08, 0x10,   // 176-191
        0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08,   // 192-207
        0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08, 0x08,   // 208-223
        0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10,   // 224-239
        0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10, 0x10   // 240-255
    ];

    /// <summary>부록 A. 글자의 심볼 값. 여러 집합에 걸친 글자는 부호집합 A 기준이고 나머지는 <see cref="SymbolFor"/>가 고친다.</summary>
    private static readonly byte[] SymbolValues =
    [
          0,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,   0,  14,  15,   //   0- 15
         16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  26,  30,  28,  29,  30,  35,   //  16- 31
         32,  53,  34,  35,  36,  37,  38,  39,  40,  41,  42,  43,  44,  45,  46,  47,   //  32- 47
         48,  49,  50,  51,  52,  53,  54,  55,  56,  57,  58,  37,  38,  39,  40,  41,   //  48- 63
         52,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,  13,  14,  15,   //  64- 79
         16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  26,  42,  43,  44,  45,  46,   //  80- 95
          0,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,  13,  14,  15,   //  96-111
         16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  26,  32,  54,  34,  35,  36,   // 112-127
         48,  49,  50,  51,  52,  53,  54,  55,  56,  57,  47,  48,  49,  50,  51,  52,   // 128-143
         53,  54,  55,  56,  57,  48,  49,  50,  51,  52,  53,  54,  55,  56,  57,  36,   // 144-159
         37,  37,  38,  39,  40,  41,  42,  43,  38,  44,  37,  39,  38,  45,  46,  40,   // 160-175
         41,  39,  40,  41,  42,  42,  47,  43,  44,  43,  44,  45,  45,  46,  47,  46,   // 176-191
          0,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,  13,  14,  15,   // 192-207
         16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  26,  32,  33,  34,  35,  36,   // 208-223
          0,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,  13,  14,  15,   // 224-239
         16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  26,  32,  33,  34,  35,  36   // 240-255
    ];

    /// <summary>
    /// 표 2·3의 래치 부호어. [옮겨 갈 집합][직전 집합] 순서다.
    /// C·D·E로 가는 래치는 같은 값을 두 번 보내고, A·B는 한 번이다.
    /// </summary>
    private static readonly byte[][][] LatchSequences =
    [
        [[], [63], [58], [58], [58]],
        [[63], [], [63], [63], [63]],
        [[62, 62], [62, 62], [], [62, 62], [62, 62]],
        [[60, 60], [60, 60], [60, 60], [], [60, 60]],
        [[61, 61], [61, 61], [61, 61], [61, 61], []]
    ];

    private static readonly int[] OpCodes =
    [
        OpDigits, OpSetA, OpSetB, OpSetE, OpSetC, OpSetD,
        OpShiftA, OpDoubleShiftA, OpTripleShiftA, OpShiftB, OpShiftE, OpShiftC, OpShiftD
    ];

    /// <summary>연산 하나가 삼키는 글자 수. 숫자 압축은 아홉 자리를 한 번에 먹는다(4.7).</summary>
    private static readonly int[] OpIntakes = [9, 1, 1, 1, 1, 1, 1, 2, 3, 1, 1, 1, 1];

    /// <summary>상태별로 따져 볼 연산. 숫자 압축은 상태와 무관해 따로 다룬다.</summary>
    private static readonly int[][] StateOps =
    [
        [1, 9, 10, 11, 12],
        [2, 6, 7, 8, 10, 11, 12],
        [3, 11, 12],
        [4, 10, 12],
        [5, 10, 11]
    ];

    private static readonly byte[] LogTable = new byte[64];

    /// <summary>역로그를 두 벌 이어 붙여 두면 로그 합을 나머지 연산 없이 바로 쓸 수 있다.</summary>
    private static readonly byte[] AntilogTable = new byte[FieldSize * 2];

    static MaxiCodeEncoder()
    {
        var p = 1;
        for (var v = 0; v < FieldSize; v++)
        {
            AntilogTable[v] = (byte)p;
            AntilogTable[FieldSize + v] = (byte)p;
            LogTable[p] = (byte)v;
            p <<= 1;
            if ((p & 0x40) != 0)
                p ^= FieldPolynomial;
        }
    }

    /// <summary>
    /// 33행 × 30열 육각 모듈. 자료 모듈과 방향 무늬만 채우고, 과녁이 덮는 가운데 자리는
    /// 자료를 담지 않으므로 false로 남긴다. 못 만들면 null.
    /// </summary>
    /// <param name="mode">2·3 구조적 운송 메시지, 4 표준, 5 전체 오류정정, 6 판독기 프로그래밍. 0이면 자동.</param>
    /// <param name="postcode">모드 2는 숫자 1~9자, 모드 3은 부호집합 A 글자 1~6자.</param>
    public static bool[,]? Encode(string value, int mode = 0, string? postcode = null,
                                  int countryCode = 0, int serviceClass = 0)
    {
        try
        {
            return EncodeCore(value, mode, postcode, countryCode, serviceClass);
        }
        catch (Exception)
        {
            // 라벨 미리보기 중에 터지면 편집기가 통째로 멎는다. 못 그리면 조용히 비운다.
            return null;
        }
    }

    private static bool[,]? EncodeCore(string value, int mode, string? postcode,
                                       int countryCode, int serviceClass)
    {
        if (value is null) return null;

        var primary = (postcode ?? "").Trim();
        if (mode == 0)
            mode = primary.Length == 0 ? 4 : IsNumericPostcode(primary) ? 2 : 3;
        if (mode is < 2 or > 6) return null;
        if (mode <= 3 && (primary.Length == 0
                          || countryCode is < 0 or > 999 || serviceClass is < 0 or > 999))
            return null;

        var source = new byte[value.Length];
        for (var i = 0; i < value.Length; i++)
        {
            // ECI를 쓰지 않으므로 라틴1 밖 글자는 담을 수 없다.
            if (value[i] > 0xFF) return null;
            source[i] = (byte)value[i];
        }

        var codewords = new byte[CodewordCount];
        if (mode == 2 && !WriteNumericPrimary(codewords, primary, countryCode, serviceClass))
            return null;
        if (mode == 3 && !WriteTextPrimary(codewords, primary, countryCode, serviceClass))
            return null;
        if (mode > 3)
            codewords[0] = (byte)mode;

        if (!WriteMessage(codewords, mode, source)) return null;
        if (mode > 3)
            // 본문 앞 아홉 부호어가 1차 메시지 자리로 간다. 남은 부호어가 2차 메시지다.
            Array.Copy(codewords, 11, codewords, 1, 9);

        AppendPrimaryErrorCorrection(codewords);
        if (mode == 5)
            AppendSecondaryErrorCorrection(codewords, SecondaryDataEec, 56);
        else
            AppendSecondaryErrorCorrection(codewords, SecondaryDataSec, 40);

        return Place(codewords);
    }

    private static bool IsNumericPostcode(string postcode)
    {
        foreach (var ch in postcode)
        {
            if (!char.IsAsciiDigit(ch) && ch != ' ') return false;
        }
        return true;
    }

    /// <summary>4.4 모드 2. 숫자 우편번호를 32비트에 담고 자릿수·국가·서비스를 뒤에 붙인다.</summary>
    private static bool WriteNumericPrimary(byte[] codewords, string postcode, int country, int service)
    {
        var space = postcode.IndexOf(' ');
        if (space >= 0)
            postcode = postcode[..space];
        if (postcode.Length is < 1 or > 9 || !IsNumericPostcode(postcode)) return false;

        // 부록 B.1.4 a). 국가 840은 "+4"를 모르면 0으로 채운다.
        if (country == 840 && postcode.Length == 5)
            postcode += "0000";

        var length = postcode.Length;
        var number = int.Parse(postcode, CultureInfo.InvariantCulture);
        codewords[0] = (byte)(((number & 0x03) << 4) | 2);
        codewords[1] = (byte)((number & 0xFC) >> 2);
        codewords[2] = (byte)((number & 0x3F00) >> 8);
        codewords[3] = (byte)((number & 0xFC000) >> 14);
        codewords[4] = (byte)((number & 0x3F00000) >> 20);
        codewords[5] = (byte)(((number & 0x3C000000) >> 26) | ((length & 0x03) << 4));
        codewords[6] = (byte)(((length & 0x3C) >> 2) | ((country & 0x03) << 4));
        codewords[7] = (byte)((country & 0xFC) >> 2);
        codewords[8] = (byte)(((country & 0x300) >> 8) | ((service & 0x0F) << 2));
        codewords[9] = (byte)((service & 0x3F0) >> 4);
        return true;
    }

    /// <summary>4.5 모드 3. 우편번호는 부호집합 A 여섯 글자로 잘리고 모자라면 빈칸으로 채운다.</summary>
    private static bool WriteTextPrimary(byte[] codewords, string postcode, int country, int service)
    {
        if (postcode.Length > 6)
            postcode = postcode[..6];
        postcode = postcode.ToUpperInvariant().PadRight(6);

        var values = new byte[6];
        for (var i = 0; i < 6; i++)
        {
            var ch = postcode[i];
            // 제어 문자(CR FS GS RS)는 우편번호에 쓸 수 없다.
            if (ch < ' ' || ch > 0xFF || (CodeSetFlags[ch] & OpSetA) == 0) return false;
            values[i] = SymbolValues[ch];
        }

        codewords[0] = (byte)(((values[5] & 0x03) << 4) | 3);
        codewords[1] = (byte)(((values[4] & 0x03) << 4) | ((values[5] & 0x3C) >> 2));
        codewords[2] = (byte)(((values[3] & 0x03) << 4) | ((values[4] & 0x3C) >> 2));
        codewords[3] = (byte)(((values[2] & 0x03) << 4) | ((values[3] & 0x3C) >> 2));
        codewords[4] = (byte)(((values[1] & 0x03) << 4) | ((values[2] & 0x3C) >> 2));
        codewords[5] = (byte)(((values[0] & 0x03) << 4) | ((values[1] & 0x3C) >> 2));
        codewords[6] = (byte)(((values[0] & 0x3C) >> 2) | ((country & 0x03) << 4));
        codewords[7] = (byte)((country & 0xFC) >> 2);
        codewords[8] = (byte)(((country & 0x300) >> 8) | ((service & 0x0F) << 2));
        codewords[9] = (byte)((service & 0x3F0) >> 4);
        return true;
    }

    /// <summary>
    /// 4.6~4.9 본문 부호화. 부호집합 A~E와 숫자 압축을 섞는 최단 경로를 뒤에서부터 되짚어 적는다.
    /// 모드 2·3은 2차 메시지 84칸만 쓰고, 모드 4~6은 1차 아홉 칸까지 이어 쓴다.
    /// </summary>
    private static bool WriteMessage(byte[] codewords, int mode, byte[] source)
    {
        var start = mode > 3 ? 11 : 20;
        var limit = (mode == 5 ? 77 : 93) + 11;

        if (source.Length == 0)
        {
            Array.Fill(codewords, PadA, start, limit - start);
            return true;
        }

        // 숫자 압축이 아홉 글자를 삼키므로 직전 열 줄만 있으면 된다. 16칸 순환 버퍼로 둔다.
        var bestLength = new int[16, StateCount];
        var bestOrigin = new int[16, StateCount];
        var pathOp = new int[source.Length, StateCount];
        var priorState = new int[source.Length, StateCount];

        var digits = 0;
        var setACount = 0;
        for (var i = 0; i < source.Length; i++)
        {
            var ch = source[i];
            digits = ch is >= (byte)'0' and <= (byte)'9' ? digits + 1 : 0;
            setACount = (CodeSetFlags[ch] & OpSetA) != 0 ? setACount + 1 : 0;

            for (var state = 0; state < StateCount; state++)
            {
                var shortest = Unreachable;
                if (digits >= 9)
                {
                    // 아홉 자리 숫자 압축은 여섯 부호어라 어떤 부호집합보다 짧다.
                    var prior = bestOrigin[(i - 9) & 0x0F, state];
                    shortest = bestLength[(i - 9) & 0x0F, prior]
                               + LatchSequences[state][prior].Length + 6;
                    pathOp[i, state] = 0;
                    priorState[i, state] = prior;
                }
                else
                {
                    foreach (var opIndex in StateOps[state])
                    {
                        var op = OpCodes[opIndex];
                        if (!CanEncode(op, ch, setACount)) continue;
                        var intake = OpIntakes[opIndex];
                        var prior = bestOrigin[(i - intake) & 0x0F, state];
                        var length = bestLength[(i - intake) & 0x0F, prior]
                                     + LatchSequences[state][prior].Length
                                     + intake + (opIndex >= ShiftOpIndex ? 1 : 0);
                        if (length >= shortest) continue;
                        pathOp[i, state] = opIndex;
                        priorState[i, state] = prior;
                        shortest = length;
                    }
                }
                bestLength[i & 0x0F, state] = shortest;
            }

            for (var state = 0; state < StateCount; state++)
            {
                var best = 0;
                var bestTotal = bestLength[i & 0x0F, 0] + LatchSequences[state][0].Length;
                for (var prior = 1; prior < StateCount; prior++)
                {
                    var total = bestLength[i & 0x0F, prior] + LatchSequences[state][prior].Length;
                    if (total >= bestTotal) continue;
                    best = prior;
                    bestTotal = total;
                }
                bestOrigin[i & 0x0F, state] = best;
            }
        }

        var lastRow = (source.Length - 1) & 0x0F;
        var endState = StateA;
        var shortestTotal = Unreachable;
        for (var state = 0; state < StateCount; state++)
        {
            if (bestLength[lastRow, state] >= shortestTotal) continue;
            endState = state;
            shortestTotal = bestLength[lastRow, state];
        }

        var cursor = start + shortestTotal;
        if (cursor > limit) return false;

        var end = cursor;
        var current = endState;
        var at = source.Length;
        while (at > 0)
        {
            var prior = priorState[at - 1, current];
            var opIndex = pathOp[at - 1, current];
            at -= OpIntakes[opIndex];
            cursor = EmitOp(codewords, cursor, source, at, OpCodes[opIndex]);
            if (current == prior) continue;
            foreach (var latch in LatchSequences[current][prior])
                codewords[--cursor] = latch;
            current = prior;
        }
        if (cursor != start) return false;

        // 부호집합 C·D에는 PAD가 없어 A로 되돌린 뒤 채운다(4.9).
        if (end < limit && endState is StateC or StateD)
            codewords[end++] = LatchToA;
        if (end < limit)
            Array.Fill(codewords, endState == StateE ? PadE : PadA, end, limit - end);
        return true;
    }

    private static bool CanEncode(int op, byte ch, int setACount)
    {
        if (op == OpDoubleShiftA) return setACount >= 2;
        if (op == OpTripleShiftA) return setACount >= 3;
        return (CodeSetFlags[ch] & op) != 0;
    }

    /// <summary>여러 부호집합에 걸친 글자는 집합마다 값이 달라 <see cref="SymbolValues"/>를 고쳐 쓴다.</summary>
    private static byte SymbolFor(int op, byte ch)
    {
        if (CodeSetFlags[ch] == (op & 0x1F) || (op & OpSetA) != 0) return SymbolValues[ch];
        if ((op & OpSetB) != 0)
        {
            var at = " ,./:".IndexOf((char)ch);
            if (at >= 0) return (byte)(47 + at);
        }
        if ((op & OpSetE) != 0 && ch is >= 28 and <= 30) return (byte)(ch + 4);
        return ch == ' ' ? (byte)59 : ch;
    }

    /// <summary>부호어를 뒤에서 앞으로 적는다. 되짚어 가는 순서라 이쪽이 자리 계산이 없다.</summary>
    private static int EmitOp(byte[] codewords, int cursor, byte[] source, int at, int op)
    {
        if (op == OpDigits)
        {
            var value = 0;
            for (var k = 0; k < 9; k++)
                value = value * 10 + (source[at + k] - '0');
            codewords[--cursor] = (byte)(value & 0x3F);
            codewords[--cursor] = (byte)((value >> 6) & 0x3F);
            codewords[--cursor] = (byte)((value >> 12) & 0x3F);
            codewords[--cursor] = (byte)((value >> 18) & 0x3F);
            codewords[--cursor] = (byte)((value >> 24) & 0x3F);
            codewords[--cursor] = NumericShift;
            return cursor;
        }
        if (op is OpDoubleShiftA or OpTripleShiftA)
        {
            var count = op == OpDoubleShiftA ? 2 : 3;
            for (var k = count - 1; k >= 0; k--)
                codewords[--cursor] = SymbolFor(OpSetA, source[at + k]);
            codewords[--cursor] = (byte)(count == 2 ? 56 : 57);
            return cursor;
        }

        codewords[--cursor] = SymbolFor(op & 0x1F, source[at]);
        if ((op & 0x20) != 0)
            // 시프트 부호어는 59(A·B) 60(C) 61(D) 62(E) 순서다.
            codewords[--cursor] = (byte)(59 + (op == OpShiftC ? 1 : 0)
                                            + (op == OpShiftD ? 2 : 0)
                                            + (op == OpShiftE ? 3 : 0));
        return cursor;
    }

    /// <summary>GF(64) 리드솔로몬 생성 다항식. 근은 α¹부터 시작한다.</summary>
    private static byte[] BuildGenerator(int count)
    {
        var poly = new byte[count + 1];
        poly[0] = 1;
        var index = 1;
        for (var i = 1; i <= count; i++)
        {
            poly[i] = 1;
            for (var k = i - 1; k > 0; k--)
            {
                if (poly[k] != 0)
                    poly[k] = AntilogTable[LogTable[poly[k]] + index];
                poly[k] ^= poly[k - 1];
            }
            poly[0] = AntilogTable[LogTable[poly[0]] + index];
            index++;
        }
        return poly;
    }

    private static byte[] Remainder(byte[] data, int count)
    {
        var poly = BuildGenerator(count);
        var logPoly = new int[count + 1];
        for (var i = 0; i <= count; i++)
            logPoly[i] = LogTable[poly[i]];

        var result = new byte[count];
        foreach (var value in data)
        {
            var feed = result[count - 1] ^ value;
            if (feed != 0)
            {
                var logFeed = LogTable[feed];
                for (var k = count - 1; k > 0; k--)
                    result[k] = (byte)(result[k - 1]
                                       ^ (poly[k] != 0 ? AntilogTable[logFeed + logPoly[k]] : 0));
                result[0] = AntilogTable[logFeed + logPoly[0]];
            }
            else
            {
                Array.Copy(result, 0, result, 1, count - 1);
                result[0] = 0;
            }
        }
        Array.Reverse(result);
        return result;
    }

    private static void AppendPrimaryErrorCorrection(byte[] codewords)
    {
        var data = new byte[PrimaryData];
        Array.Copy(codewords, 0, data, 0, PrimaryData);
        var ecc = Remainder(data, PrimaryData);
        Array.Copy(ecc, 0, codewords, PrimaryData, PrimaryData);
    }

    /// <summary>
    /// 4.10.3. 2차 메시지는 홀·짝 두 갈래로 갈라 각각 오류정정을 붙인 뒤 다시 번갈아 끼운다.
    /// 한쪽에 몰린 손상이 두 갈래로 나뉘어 정정 능력이 올라간다.
    /// </summary>
    private static void AppendSecondaryErrorCorrection(byte[] codewords, int dataLength, int eccLength)
    {
        var half = dataLength / 2;
        var eccHalf = eccLength / 2;
        for (var parity = 0; parity < 2; parity++)
        {
            var data = new byte[half];
            for (var k = 0; k < half; k++)
                data[k] = codewords[20 + parity + k * 2];
            var ecc = Remainder(data, eccHalf);
            for (var k = 0; k < eccHalf; k++)
                codewords[20 + dataLength + parity + k * 2] = ecc[k];
        }
    }

    /// <summary>그림 5의 자리표대로 부호어 144개의 비트 864개를 육각 격자에 흘려 넣는다.</summary>
    private static bool[,] Place(byte[] codewords)
    {
        var grid = new bool[Rows, Columns];
        for (var row = 0; row < Rows; row++)
        {
            for (var col = 0; col < Columns; col++)
            {
                var sequence = ModuleSequence[row * Columns + col] + 5;
                var block = sequence / 6;
                if (block == 0) continue;
                grid[row, col] = ((codewords[block - 1] >> (5 - sequence % 6)) & 1) != 0;
            }
        }
        foreach (var (row, col) in OrientationModules)
            grid[row, col] = true;
        return grid;
    }
}
