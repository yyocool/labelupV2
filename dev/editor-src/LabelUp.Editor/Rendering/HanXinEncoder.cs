using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// Han Xin Code(汉信码). 중국 국가표준 GB/T 21049를 그대로 국제화한 ISO/IEC 20830 절차다.
/// ZXing.Net은 이 규격을 읽지도 쓰지도 못해서 libzint의 hanxin.c·hanxin.h·reedsol.c를 옮겼다.
/// 원본은 BSD-3-Clause(Copyright © 2009-2026 Robin Stuart, © 2016 Zoe Stuart)이고
/// 모드 분할 동적계획법만 Project Nayuki(MIT)에서 왔다 — 둘 다 원본 공개 의무가 없는 조건이다.
/// 「Han Xin Code」 실측: 27×27 모듈 = 판형 3·오류정정 L2·마스크 1. 자동 선택 결과와 같다.
/// </summary>
internal static class HanXinEncoder
{
    /// <summary>표 B1. 판형별 전체 부호어 수.</summary>
    private static readonly int[] TotalCodewords =
    [
        25, 37, 50, 54, 69, 84, 100, 117, 136, 155,
        161, 181, 203, 225, 249, 273, 299, 325, 353, 381,
        411, 422, 453, 485, 518, 552, 587, 623, 660, 698,
        737, 754, 794, 836, 878, 922, 966, 1011, 1058, 1105,
        1126, 1175, 1224, 1275, 1327, 1380, 1434, 1489, 1513, 1569,
        1628, 1686, 1745, 1805, 1867, 1929, 1992, 2021, 2086, 2151,
        2218, 2286, 2355, 2425, 2496, 2528, 2600, 2673, 2749, 2824,
        2900, 2977, 3056, 3135, 3171, 3252, 3334, 3416, 3500, 3585,
        3671, 3758, 3798, 3886
    ];

    /// <summary>표 B1. [오류정정 등급-1][판형-1]별 자료 부호어 수.</summary>
    private static readonly int[][] DataCodewords =
    [
        [
            21, 31, 42, 46, 57, 70, 84, 99, 114, 131,
            135, 153, 171, 189, 209, 229, 251, 273, 297, 321,
            345, 354, 381, 407, 436, 464, 493, 523, 554, 586,
            619, 634, 666, 702, 738, 774, 812, 849, 888, 929,
            946, 987, 1028, 1071, 1115, 1160, 1204, 1251, 1271, 1317,
            1368, 1416, 1465, 1517, 1569, 1621, 1674, 1697, 1752, 1807,
            1864, 1920, 1979, 2037, 2096, 2124, 2184, 2245, 2309, 2372,
            2436, 2501, 2568, 2633, 2663, 2732, 2800, 2870, 2940, 3011,
            3083, 3156, 3190, 3264
        ],
        [
            17, 25, 34, 38, 49, 58, 70, 81, 96, 109,
            113, 127, 143, 157, 175, 191, 209, 227, 247, 267,
            287, 296, 317, 339, 362, 386, 411, 437, 462, 488,
            515, 528, 556, 586, 614, 646, 676, 707, 740, 773,
            788, 823, 856, 893, 929, 966, 1004, 1043, 1059, 1099,
            1140, 1180, 1221, 1263, 1307, 1351, 1394, 1415, 1460, 1505,
            1552, 1600, 1649, 1697, 1748, 1770, 1820, 1871, 1925, 1976,
            2030, 2083, 2140, 2195, 2219, 2276, 2334, 2392, 2450, 2509,
            2569, 2630, 2658, 2720
        ],
        [
            13, 19, 26, 30, 37, 46, 54, 63, 74, 83,
            87, 97, 109, 121, 135, 147, 161, 175, 191, 205,
            221, 228, 245, 261, 280, 298, 317, 337, 358, 376,
            397, 408, 428, 452, 474, 498, 522, 545, 572, 597,
            608, 635, 660, 689, 717, 746, 774, 805, 817, 847,
            880, 910, 943, 975, 1009, 1041, 1076, 1091, 1126, 1161,
            1198, 1234, 1271, 1309, 1348, 1366, 1404, 1443, 1485, 1524,
            1566, 1607, 1650, 1693, 1713, 1756, 1800, 1844, 1890, 1935,
            1983, 2030, 2050, 2098
        ],
        [
            9, 15, 20, 22, 27, 34, 40, 47, 54, 61,
            65, 73, 81, 89, 99, 109, 119, 129, 141, 153,
            165, 168, 181, 195, 208, 220, 235, 251, 264, 280,
            295, 302, 318, 334, 352, 368, 386, 405, 424, 441,
            450, 469, 490, 509, 531, 552, 574, 595, 605, 627,
            652, 674, 697, 721, 747, 771, 796, 809, 834, 861,
            892, 914, 941, 969, 998, 1012, 1040, 1069, 1099, 1130,
            1160, 1191, 1222, 1253, 1269, 1300, 1334, 1366, 1400, 1433,
            1469, 1504, 1520, 1554
        ]
    ];

    /// <summary>부속서 A의 k. 정렬 무늬 격자에서 앞쪽 m칸의 간격이다.</summary>
    private static readonly int[] ModuleK =
    [
        0, 0, 0, 14, 16, 16, 17, 18, 19, 20,
        14, 15, 16, 16, 17, 17, 18, 19, 20, 20,
        21, 16, 17, 17, 18, 18, 19, 19, 20, 20,
        21, 17, 17, 18, 18, 19, 19, 19, 20, 20,
        17, 17, 18, 18, 18, 19, 19, 19, 17, 17,
        18, 18, 18, 18, 19, 19, 19, 17, 17, 18,
        18, 18, 18, 19, 19, 17, 17, 17, 18, 18,
        18, 18, 19, 19, 17, 17, 17, 18, 18, 18,
        18, 18, 17, 17
    ];

    /// <summary>부속서 A의 r. 남는 칸의 간격은 r-1이다.</summary>
    private static readonly int[] ModuleR =
    [
        0, 0, 0, 15, 15, 17, 18, 19, 20, 21,
        15, 15, 15, 17, 17, 19, 19, 19, 19, 21,
        21, 17, 16, 18, 17, 19, 18, 20, 19, 21,
        20, 17, 19, 17, 19, 17, 19, 21, 19, 21,
        18, 20, 17, 19, 21, 18, 20, 22, 17, 19,
        15, 17, 19, 21, 17, 19, 21, 18, 20, 15,
        17, 19, 21, 16, 18, 17, 19, 21, 15, 17,
        19, 21, 15, 17, 18, 20, 22, 15, 17, 19,
        21, 23, 17, 19
    ];

    /// <summary>부속서 A의 m. 간격이 k인 칸의 개수.</summary>
    private static readonly int[] ModuleM =
    [
        0, 0, 0, 1, 1, 1, 1, 1, 1, 1,
        2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
        2, 3, 3, 3, 3, 3, 3, 3, 3, 3,
        3, 4, 4, 4, 4, 4, 4, 4, 4, 4,
        5, 5, 5, 5, 5, 5, 5, 5, 6, 6,
        6, 6, 6, 6, 6, 6, 6, 7, 7, 7,
        7, 7, 7, 7, 7, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 9, 9, 9, 9, 9, 9,
        9, 9, 10, 10
    ];

    /// <summary>
    /// 표 D1. [판형-1][등급-1]마다 (블록 수, 자료 부호어, 오류정정 부호어)가 세 묶음씩 이어진다.
    /// 블록 수 0은 그 묶음을 쓰지 않는다는 뜻이다.
    /// </summary>
    private static readonly int[] EccBlocks =
    [
        1, 21, 4, 0, 0, 0, 0, 0, 0,             // 판형 1
        1, 17, 8, 0, 0, 0, 0, 0, 0,
        1, 13, 12, 0, 0, 0, 0, 0, 0,
        1, 9, 16, 0, 0, 0, 0, 0, 0,
        1, 31, 6, 0, 0, 0, 0, 0, 0,             // 판형 2
        1, 25, 12, 0, 0, 0, 0, 0, 0,
        1, 19, 18, 0, 0, 0, 0, 0, 0,
        1, 15, 22, 0, 0, 0, 0, 0, 0,
        1, 42, 8, 0, 0, 0, 0, 0, 0,             // 판형 3
        1, 34, 16, 0, 0, 0, 0, 0, 0,
        1, 26, 24, 0, 0, 0, 0, 0, 0,
        1, 20, 30, 0, 0, 0, 0, 0, 0,
        1, 46, 8, 0, 0, 0, 0, 0, 0,             // 판형 4
        1, 38, 16, 0, 0, 0, 0, 0, 0,
        1, 30, 24, 0, 0, 0, 0, 0, 0,
        1, 22, 32, 0, 0, 0, 0, 0, 0,
        1, 57, 12, 0, 0, 0, 0, 0, 0,            // 판형 5
        1, 49, 20, 0, 0, 0, 0, 0, 0,
        1, 37, 32, 0, 0, 0, 0, 0, 0,
        1, 14, 20, 1, 13, 22, 0, 0, 0,
        1, 70, 14, 0, 0, 0, 0, 0, 0,            // 판형 6
        1, 58, 26, 0, 0, 0, 0, 0, 0,
        1, 24, 20, 1, 22, 18, 0, 0, 0,
        1, 16, 24, 1, 18, 26, 0, 0, 0,
        1, 84, 16, 0, 0, 0, 0, 0, 0,            // 판형 7
        1, 70, 30, 0, 0, 0, 0, 0, 0,
        1, 26, 22, 1, 28, 24, 0, 0, 0,
        2, 14, 20, 1, 12, 20, 0, 0, 0,
        1, 99, 18, 0, 0, 0, 0, 0, 0,            // 판형 8
        1, 40, 18, 1, 41, 18, 0, 0, 0,
        1, 31, 26, 1, 32, 28, 0, 0, 0,
        2, 16, 24, 1, 15, 22, 0, 0, 0,
        1, 114, 22, 0, 0, 0, 0, 0, 0,           // 판형 9
        2, 48, 20, 0, 0, 0, 0, 0, 0,
        2, 24, 20, 1, 26, 22, 0, 0, 0,
        2, 18, 28, 1, 18, 26, 0, 0, 0,
        1, 131, 24, 0, 0, 0, 0, 0, 0,           // 판형 10
        1, 52, 22, 1, 57, 24, 0, 0, 0,
        2, 27, 24, 1, 29, 24, 0, 0, 0,
        2, 21, 32, 1, 19, 30, 0, 0, 0,
        1, 135, 26, 0, 0, 0, 0, 0, 0,           // 판형 11
        1, 56, 24, 1, 57, 24, 0, 0, 0,
        2, 28, 24, 1, 31, 26, 0, 0, 0,
        2, 22, 32, 1, 21, 32, 0, 0, 0,
        1, 153, 28, 0, 0, 0, 0, 0, 0,           // 판형 12
        1, 62, 26, 1, 65, 28, 0, 0, 0,
        2, 32, 28, 1, 33, 28, 0, 0, 0,
        3, 17, 26, 1, 22, 30, 0, 0, 0,
        1, 86, 16, 1, 85, 16, 0, 0, 0,          // 판형 13
        1, 71, 30, 1, 72, 30, 0, 0, 0,
        2, 37, 32, 1, 35, 30, 0, 0, 0,
        3, 20, 30, 1, 21, 32, 0, 0, 0,
        1, 94, 18, 1, 95, 18, 0, 0, 0,          // 판형 14
        2, 51, 22, 1, 55, 24, 0, 0, 0,
        3, 30, 26, 1, 31, 26, 0, 0, 0,
        4, 18, 28, 1, 17, 24, 0, 0, 0,
        1, 104, 20, 1, 105, 20, 0, 0, 0,        // 판형 15
        2, 57, 24, 1, 61, 26, 0, 0, 0,
        3, 33, 28, 1, 36, 30, 0, 0, 0,
        4, 20, 30, 1, 19, 30, 0, 0, 0,
        1, 115, 22, 1, 114, 22, 0, 0, 0,        // 판형 16
        2, 65, 28, 1, 61, 26, 0, 0, 0,
        3, 38, 32, 1, 33, 30, 0, 0, 0,
        5, 19, 28, 1, 14, 24, 0, 0, 0,
        1, 126, 24, 1, 125, 24, 0, 0, 0,        // 판형 17
        2, 70, 30, 1, 69, 30, 0, 0, 0,
        4, 33, 28, 1, 29, 26, 0, 0, 0,
        5, 20, 30, 1, 19, 30, 0, 0, 0,
        1, 136, 26, 1, 137, 26, 0, 0, 0,        // 판형 18
        3, 56, 24, 1, 59, 26, 0, 0, 0,
        5, 35, 30, 0, 0, 0, 0, 0, 0,
        6, 18, 28, 1, 21, 28, 0, 0, 0,
        1, 148, 28, 1, 149, 28, 0, 0, 0,        // 판형 19
        3, 61, 26, 1, 64, 28, 0, 0, 0,
        7, 24, 20, 1, 23, 22, 0, 0, 0,
        6, 20, 30, 1, 21, 32, 0, 0, 0,
        3, 107, 20, 0, 0, 0, 0, 0, 0,           // 판형 20
        3, 65, 28, 1, 72, 30, 0, 0, 0,
        7, 26, 22, 1, 23, 22, 0, 0, 0,
        7, 19, 28, 1, 20, 32, 0, 0, 0,
        3, 115, 22, 0, 0, 0, 0, 0, 0,           // 판형 21
        4, 56, 24, 1, 63, 28, 0, 0, 0,
        7, 28, 24, 1, 25, 22, 0, 0, 0,
        8, 18, 28, 1, 21, 22, 0, 0, 0,
        2, 116, 22, 1, 122, 24, 0, 0, 0,        // 판형 22
        4, 56, 24, 1, 72, 30, 0, 0, 0,
        7, 28, 24, 1, 32, 26, 0, 0, 0,
        8, 18, 28, 1, 24, 30, 0, 0, 0,
        3, 127, 24, 0, 0, 0, 0, 0, 0,           // 판형 23
        5, 51, 22, 1, 62, 26, 0, 0, 0,
        7, 30, 26, 1, 35, 26, 0, 0, 0,
        8, 20, 30, 1, 21, 32, 0, 0, 0,
        2, 135, 26, 1, 137, 26, 0, 0, 0,        // 판형 24
        5, 56, 24, 1, 59, 26, 0, 0, 0,
        7, 33, 28, 1, 30, 28, 0, 0, 0,
        11, 16, 24, 1, 19, 26, 0, 0, 0,
        3, 105, 20, 1, 121, 22, 0, 0, 0,        // 판형 25
        5, 61, 26, 1, 57, 26, 0, 0, 0,
        9, 28, 24, 1, 28, 22, 0, 0, 0,
        10, 19, 28, 1, 18, 30, 0, 0, 0,
        2, 157, 30, 1, 150, 28, 0, 0, 0,        // 판형 26
        5, 65, 28, 1, 61, 26, 0, 0, 0,
        8, 33, 28, 1, 34, 30, 0, 0, 0,
        10, 19, 28, 2, 15, 26, 0, 0, 0,
        3, 126, 24, 1, 115, 22, 0, 0, 0,        // 판형 27
        7, 51, 22, 1, 54, 22, 0, 0, 0,
        8, 35, 30, 1, 37, 30, 0, 0, 0,
        15, 15, 22, 1, 10, 22, 0, 0, 0,
        4, 105, 20, 1, 103, 20, 0, 0, 0,        // 판형 28
        7, 56, 24, 1, 45, 18, 0, 0, 0,
        10, 31, 26, 1, 27, 26, 0, 0, 0,
        10, 17, 26, 3, 20, 28, 1, 21, 28,
        3, 139, 26, 1, 137, 28, 0, 0, 0,        // 판형 29
        6, 66, 28, 1, 66, 30, 0, 0, 0,
        9, 36, 30, 1, 34, 32, 0, 0, 0,
        13, 19, 28, 1, 17, 32, 0, 0, 0,
        6, 84, 16, 1, 82, 16, 0, 0, 0,          // 판형 30
        6, 70, 30, 1, 68, 30, 0, 0, 0,
        7, 35, 30, 3, 33, 28, 1, 32, 28,
        13, 20, 30, 1, 20, 28, 0, 0, 0,
        5, 105, 20, 1, 94, 18, 0, 0, 0,         // 판형 31
        6, 74, 32, 1, 71, 30, 0, 0, 0,
        11, 33, 28, 1, 34, 32, 0, 0, 0,
        13, 19, 28, 3, 16, 26, 0, 0, 0,
        4, 127, 24, 1, 126, 24, 0, 0, 0,        // 판형 32
        7, 66, 28, 1, 66, 30, 0, 0, 0,
        12, 30, 24, 1, 24, 28, 1, 24, 30,
        15, 19, 28, 1, 17, 32, 0, 0, 0,
        7, 84, 16, 1, 78, 16, 0, 0, 0,          // 판형 33
        7, 70, 30, 1, 66, 28, 0, 0, 0,
        12, 33, 28, 1, 32, 30, 0, 0, 0,
        14, 21, 32, 1, 24, 28, 0, 0, 0,
        5, 117, 22, 1, 117, 24, 0, 0, 0,        // 판형 34
        8, 66, 28, 1, 58, 26, 0, 0, 0,
        11, 38, 32, 1, 34, 32, 0, 0, 0,
        15, 20, 30, 2, 17, 26, 0, 0, 0,
        4, 148, 28, 1, 146, 28, 0, 0, 0,        // 판형 35
        8, 68, 30, 1, 70, 24, 0, 0, 0,
        10, 36, 32, 3, 38, 28, 0, 0, 0,
        16, 19, 28, 3, 16, 26, 0, 0, 0,
        4, 126, 24, 2, 135, 26, 0, 0, 0,        // 판형 36
        8, 70, 28, 2, 43, 26, 0, 0, 0,
        13, 32, 28, 2, 41, 30, 0, 0, 0,
        17, 19, 28, 3, 15, 26, 0, 0, 0,
        5, 136, 26, 1, 132, 24, 0, 0, 0,        // 판형 37
        5, 67, 30, 4, 68, 28, 1, 69, 28,
        14, 35, 30, 1, 32, 24, 0, 0, 0,
        18, 18, 26, 3, 16, 28, 1, 14, 28,
        3, 142, 26, 3, 141, 28, 0, 0, 0,        // 판형 38
        8, 70, 30, 1, 73, 32, 1, 74, 32,
        12, 34, 30, 3, 34, 26, 1, 35, 28,
        18, 21, 32, 1, 27, 30, 0, 0, 0,
        5, 116, 22, 2, 103, 20, 1, 102, 20,     // 판형 39
        9, 74, 32, 1, 74, 30, 0, 0, 0,
        14, 34, 28, 2, 32, 32, 1, 32, 30,
        19, 21, 32, 1, 25, 26, 0, 0, 0,
        7, 116, 22, 1, 117, 22, 0, 0, 0,        // 판형 40
        11, 65, 28, 1, 58, 24, 0, 0, 0,
        15, 38, 32, 1, 27, 28, 0, 0, 0,
        20, 20, 30, 1, 20, 32, 1, 21, 32,
        6, 136, 26, 1, 130, 24, 0, 0, 0,        // 판형 41
        11, 66, 28, 1, 62, 30, 0, 0, 0,
        14, 34, 28, 3, 34, 32, 1, 30, 30,
        18, 20, 30, 3, 20, 28, 2, 15, 26,
        5, 105, 20, 2, 115, 22, 2, 116, 22,     // 판형 42
        10, 75, 32, 1, 73, 32, 0, 0, 0,
        16, 38, 32, 1, 27, 28, 0, 0, 0,
        22, 19, 28, 2, 16, 30, 1, 19, 30,
        6, 147, 28, 1, 146, 28, 0, 0, 0,        // 판형 43
        11, 66, 28, 2, 65, 30, 0, 0, 0,
        18, 33, 28, 2, 33, 30, 0, 0, 0,
        22, 21, 32, 1, 28, 30, 0, 0, 0,
        6, 116, 22, 3, 125, 24, 0, 0, 0,        // 판형 44
        11, 75, 32, 1, 68, 30, 0, 0, 0,
        13, 35, 28, 6, 34, 32, 1, 30, 30,
        23, 21, 32, 1, 26, 30, 0, 0, 0,
        7, 105, 20, 4, 95, 18, 0, 0, 0,         // 판형 45
        12, 67, 28, 1, 63, 30, 1, 62, 32,
        21, 31, 26, 2, 33, 32, 0, 0, 0,
        23, 21, 32, 2, 24, 30, 0, 0, 0,
        10, 116, 22, 0, 0, 0, 0, 0, 0,          // 판형 46
        12, 74, 32, 1, 78, 30, 0, 0, 0,
        18, 37, 32, 1, 39, 30, 1, 41, 28,
        25, 21, 32, 1, 27, 28, 0, 0, 0,
        5, 126, 24, 4, 115, 22, 1, 114, 22,     // 판형 47
        12, 67, 28, 2, 66, 32, 1, 68, 30,
        21, 35, 30, 1, 39, 30, 0, 0, 0,
        26, 21, 32, 1, 28, 28, 0, 0, 0,
        9, 126, 24, 1, 117, 22, 0, 0, 0,        // 판형 48
        13, 75, 32, 1, 68, 30, 0, 0, 0,
        20, 35, 30, 3, 35, 28, 0, 0, 0,
        27, 21, 32, 1, 28, 30, 0, 0, 0,
        9, 126, 24, 1, 137, 26, 0, 0, 0,        // 판형 49
        13, 71, 30, 2, 68, 32, 0, 0, 0,
        20, 37, 32, 1, 39, 28, 1, 38, 28,
        24, 20, 32, 5, 25, 28, 0, 0, 0,
        8, 147, 28, 1, 141, 28, 0, 0, 0,        // 판형 50
        10, 73, 32, 4, 74, 30, 1, 73, 30,
        16, 36, 32, 6, 39, 30, 1, 37, 30,
        27, 21, 32, 3, 20, 26, 0, 0, 0,
        9, 137, 26, 1, 135, 26, 0, 0, 0,        // 판형 51
        12, 70, 30, 4, 75, 32, 0, 0, 0,
        24, 35, 30, 1, 40, 28, 0, 0, 0,
        23, 20, 32, 8, 24, 30, 0, 0, 0,
        14, 95, 18, 1, 86, 18, 0, 0, 0,         // 판형 52
        13, 73, 32, 3, 77, 30, 0, 0, 0,
        24, 35, 30, 2, 35, 28, 0, 0, 0,
        26, 21, 32, 5, 21, 30, 1, 23, 30,
        9, 147, 28, 1, 142, 28, 0, 0, 0,        // 판형 53
        10, 73, 30, 6, 70, 32, 1, 71, 32,
        25, 35, 30, 2, 34, 26, 0, 0, 0,
        29, 21, 32, 4, 22, 30, 0, 0, 0,
        11, 126, 24, 1, 131, 24, 0, 0, 0,       // 판형 54
        16, 74, 32, 1, 79, 30, 0, 0, 0,
        25, 38, 32, 1, 25, 30, 0, 0, 0,
        33, 21, 32, 1, 28, 28, 0, 0, 0,
        14, 105, 20, 1, 99, 18, 0, 0, 0,        // 판형 55
        19, 65, 28, 1, 72, 28, 0, 0, 0,
        24, 37, 32, 2, 40, 30, 1, 41, 30,
        31, 21, 32, 4, 24, 32, 0, 0, 0,
        10, 147, 28, 1, 151, 28, 0, 0, 0,       // 판형 56
        15, 71, 30, 3, 71, 32, 1, 73, 32,
        24, 37, 32, 3, 38, 30, 1, 39, 30,
        36, 19, 30, 3, 29, 26, 0, 0, 0,
        15, 105, 20, 1, 99, 18, 0, 0, 0,        // 판형 57
        19, 70, 30, 1, 64, 28, 0, 0, 0,
        27, 38, 32, 2, 25, 26, 0, 0, 0,
        38, 20, 30, 2, 18, 28, 0, 0, 0,
        14, 105, 20, 1, 113, 22, 1, 114, 22,    // 판형 58
        17, 67, 30, 3, 92, 32, 0, 0, 0,
        30, 35, 30, 1, 41, 30, 0, 0, 0,
        36, 21, 32, 1, 26, 30, 1, 27, 30,
        11, 146, 28, 1, 146, 26, 0, 0, 0,       // 판형 59
        20, 70, 30, 1, 60, 26, 0, 0, 0,
        29, 38, 32, 1, 24, 32, 0, 0, 0,
        40, 20, 30, 2, 17, 26, 0, 0, 0,
        3, 137, 26, 1, 136, 26, 10, 126, 24,    // 판형 60
        22, 65, 28, 1, 75, 30, 0, 0, 0,
        30, 37, 32, 1, 51, 30, 0, 0, 0,
        42, 20, 30, 1, 21, 30, 0, 0, 0,
        12, 126, 24, 2, 118, 22, 1, 116, 22,    // 판형 61
        19, 74, 32, 1, 74, 30, 1, 72, 28,
        30, 38, 32, 2, 29, 30, 0, 0, 0,
        39, 20, 32, 2, 37, 26, 1, 38, 26,
        12, 126, 24, 3, 136, 26, 0, 0, 0,       // 판형 62
        21, 70, 30, 2, 65, 28, 0, 0, 0,
        34, 35, 30, 1, 44, 32, 0, 0, 0,
        42, 20, 30, 2, 19, 28, 2, 18, 28,
        12, 126, 24, 3, 117, 22, 1, 116, 22,    // 판형 63
        25, 61, 26, 2, 62, 28, 0, 0, 0,
        34, 35, 30, 1, 40, 32, 1, 41, 32,
        45, 20, 30, 1, 20, 32, 1, 21, 32,
        15, 105, 20, 2, 115, 22, 2, 116, 22,    // 판형 64
        25, 65, 28, 1, 72, 28, 0, 0, 0,
        18, 35, 30, 17, 37, 32, 1, 50, 32,
        42, 20, 30, 6, 19, 28, 1, 15, 28,
        19, 105, 20, 1, 101, 20, 0, 0, 0,       // 판형 65
        33, 51, 22, 1, 65, 22, 0, 0, 0,
        40, 33, 28, 1, 28, 28, 0, 0, 0,
        49, 20, 30, 1, 18, 28, 0, 0, 0,
        18, 105, 20, 2, 117, 22, 0, 0, 0,       // 판형 66
        26, 65, 28, 1, 80, 30, 0, 0, 0,
        35, 35, 30, 3, 35, 28, 1, 36, 28,
        52, 18, 28, 2, 38, 30, 0, 0, 0,
        26, 84, 16, 0, 0, 0, 0, 0, 0,           // 판형 67
        26, 70, 30, 0, 0, 0, 0, 0, 0,
        45, 31, 26, 1, 9, 26, 0, 0, 0,
        52, 20, 30, 0, 0, 0, 0, 0, 0,
        16, 126, 24, 1, 114, 22, 1, 115, 22,    // 판형 68
        23, 70, 30, 3, 65, 28, 1, 66, 28,
        40, 35, 30, 1, 43, 30, 0, 0, 0,
        46, 20, 30, 7, 19, 28, 1, 16, 28,
        19, 116, 22, 1, 105, 22, 0, 0, 0,       // 판형 69
        20, 70, 30, 7, 66, 28, 1, 63, 28,
        40, 35, 30, 1, 42, 32, 1, 43, 32,
        54, 20, 30, 1, 19, 30, 0, 0, 0,
        17, 126, 24, 2, 115, 22, 0, 0, 0,       // 판형 70
        24, 70, 30, 4, 74, 32, 0, 0, 0,
        48, 31, 26, 2, 18, 26, 0, 0, 0,
        54, 19, 28, 6, 15, 26, 1, 14, 26,
        29, 84, 16, 0, 0, 0, 0, 0, 0,           // 판형 71
        29, 70, 30, 0, 0, 0, 0, 0, 0,
        6, 34, 30, 3, 36, 30, 38, 33, 28,
        58, 20, 30, 0, 0, 0, 0, 0, 0,
        16, 147, 28, 1, 149, 28, 0, 0, 0,       // 판형 72
        31, 66, 28, 1, 37, 26, 0, 0, 0,
        48, 33, 28, 1, 23, 26, 0, 0, 0,
        53, 20, 30, 6, 19, 28, 1, 17, 28,
        20, 115, 22, 2, 134, 24, 0, 0, 0,       // 판형 73
        29, 66, 28, 2, 56, 26, 2, 57, 26,
        45, 36, 30, 2, 15, 28, 0, 0, 0,
        59, 20, 30, 2, 21, 32, 0, 0, 0,
        17, 147, 28, 1, 134, 26, 0, 0, 0,       // 판형 74
        26, 70, 30, 5, 75, 32, 0, 0, 0,
        47, 35, 30, 1, 48, 32, 0, 0, 0,
        64, 18, 28, 2, 33, 30, 1, 35, 30,
        22, 115, 22, 1, 133, 24, 0, 0, 0,       // 판형 75
        33, 65, 28, 1, 74, 28, 0, 0, 0,
        43, 36, 30, 5, 27, 28, 1, 30, 28,
        57, 20, 30, 5, 21, 32, 1, 24, 32,
        18, 136, 26, 2, 142, 26, 0, 0, 0,       // 판형 76
        33, 66, 28, 2, 49, 26, 0, 0, 0,
        48, 35, 30, 2, 38, 28, 0, 0, 0,
        64, 20, 30, 1, 20, 32, 0, 0, 0,
        19, 126, 24, 2, 135, 26, 1, 136, 26,    // 판형 77
        32, 66, 28, 2, 55, 26, 2, 56, 26,
        49, 36, 30, 2, 18, 32, 0, 0, 0,
        65, 18, 28, 5, 27, 30, 1, 29, 30,
        20, 137, 26, 1, 130, 26, 0, 0, 0,       // 판형 78
        30, 75, 32, 2, 71, 32, 0, 0, 0,
        46, 35, 30, 6, 39, 32, 0, 0, 0,
        3, 12, 30, 70, 19, 28, 0, 0, 0,
        20, 147, 28, 0, 0, 0, 0, 0, 0,          // 판형 79
        35, 70, 30, 0, 0, 0, 0, 0, 0,
        49, 35, 30, 5, 35, 28, 0, 0, 0,
        70, 20, 30, 0, 0, 0, 0, 0, 0,
        21, 136, 26, 1, 155, 28, 0, 0, 0,       // 판형 80
        34, 70, 30, 1, 64, 28, 1, 65, 28,
        54, 35, 30, 1, 45, 30, 0, 0, 0,
        68, 20, 30, 3, 18, 28, 1, 19, 28,
        19, 126, 24, 5, 115, 22, 1, 114, 22,    // 판형 81
        33, 70, 30, 3, 65, 28, 1, 64, 28,
        52, 35, 30, 3, 41, 32, 1, 40, 32,
        67, 20, 30, 5, 21, 32, 1, 24, 32,
        2, 150, 28, 21, 136, 26, 0, 0, 0,       // 판형 82
        32, 70, 30, 6, 65, 28, 0, 0, 0,
        52, 38, 32, 2, 27, 32, 0, 0, 0,
        73, 20, 30, 2, 22, 32, 0, 0, 0,
        21, 126, 24, 4, 136, 26, 0, 0, 0,       // 판형 83
        30, 74, 32, 6, 73, 30, 0, 0, 0,
        54, 35, 30, 4, 40, 32, 0, 0, 0,
        75, 20, 30, 1, 20, 28, 0, 0, 0,
        30, 105, 20, 1, 114, 22, 0, 0, 0,       // 판형 84
        3, 45, 22, 55, 47, 20, 0, 0, 0,
        2, 26, 26, 62, 33, 28, 0, 0, 0,
        79, 18, 28, 4, 33, 30, 0, 0, 0
    ];

    private const int MaxVersion = 84;

    /// <summary>비용을 1/6비트로 재면 숫자 3자리(10/3비트)와 문자 반 글자도 정수로 떨어진다.</summary>
    private const int CostUnit = 6;

    private const int ModeNumeric = 0;
    private const int ModeText = 1;
    private const int ModeBinary = 2;
    private const int ModeCount = 3;
    private const int ModeNone = -1;

    private const int EccAuto = 0;
    private const int EccInvalid = -1;

    /// <summary>모드를 처음 여는 값. 바이트 모드만 4비트 지시자 뒤에 13비트 글자 수가 더 붙는다.</summary>
    private static readonly int[] HeadCosts = [4 * CostUnit, 4 * CostUnit, (4 + 13) * CostUnit];

    /// <summary>[이전 모드][다음 모드] 전환 값. 이전 모드의 종료 부호에 새 지시자를 더한 값이다.</summary>
    private static readonly int[][] SwitchCosts =
    [
        [0, (10 + 4) * CostUnit, (10 + 4 + 13) * CostUnit],
        [(6 + 4) * CostUnit, 0, (6 + 4 + 13) * CostUnit],
        [4 * CostUnit, 4 * CostUnit, 0]
    ];

    /// <summary>마지막 글자 뒤 종료 부호 값. 바이트 모드는 글자 수를 미리 알려서 종료 부호가 없다.</summary>
    private static readonly int[] EodCosts = [10 * CostUnit, 6 * CostUnit, 0];

    /// <summary>좌상단 찾기 무늬(5.4.2). 행마다 7비트를 0x40부터 훑는다.</summary>
    private static readonly int[] FinderTopLeft = [0x7F, 0x40, 0x5F, 0x50, 0x57, 0x57, 0x57];

    /// <summary>우상단·좌하단 찾기 무늬.</summary>
    private static readonly int[] FinderCorner = [0x7F, 0x01, 0x7D, 0x05, 0x75, 0x75, 0x75];

    /// <summary>우하단 찾기 무늬. 나머지 셋과 달리 위아래가 뒤집혀 있다.</summary>
    private static readonly int[] FinderBottomRight = [0x75, 0x75, 0x75, 0x05, 0x7D, 0x01, 0x7F];

    /// <summary>버전·오류정정 등급을 지정하지 않으면 데이터에 맞는 최소 조합을 고른다.</summary>
    /// <param name="wantVersion">판형 1~84. 0이면 자동.</param>
    /// <param name="wantEcc">"L1"~"L4". 비어 있으면 남는 용량만큼 등급을 올린다.</param>
    /// <param name="wantMask">0~3. 음수면 표 9의 벌점 규칙대로 고른다.</param>
    public static BitMatrix? Encode(string value, int wantVersion = 0, string? wantEcc = null, int wantMask = -1)
    {
        if (string.IsNullOrEmpty(value)) return null;
        if (wantVersion is < 0 or > MaxVersion || wantMask > 3) return null;

        var ecc = ParseEcc(wantEcc);
        if (ecc == EccInvalid) return null;

        try
        {
            // ECI를 붙이지 않으면 규격 기본 해석이 ISO/IEC 8859-1이다. GB 18030 한자 모드는
            // 그 부호화가 WASM 기본 제공에 없어서 쓰지 않는다.
            if (value.Any(c => c > 0xFF)) return null;
            var data = Encoding.Latin1.GetBytes(value);

            var bits = BuildBitStream(data, DefineModes(data));
            return Build(bits, wantVersion, ecc, wantMask);
        }
        catch (Exception)
        {
            // 라벨 미리보기 중에 터지면 편집기가 통째로 멎는다. 못 그리면 조용히 비운다.
            return null;
        }
    }

    /// <summary>규격은 등급을 L1~L4로 부르지만 애니라벨 속성에는 QR과 같은 L·M·Q·H가 들어온다.</summary>
    private static int ParseEcc(string? want) => want?.Trim().ToUpperInvariant() switch
    {
        null or "" => EccAuto,
        "L1" or "1" or "L" => 1,
        "L2" or "2" or "M" => 2,
        "L3" or "3" or "Q" => 3,
        "L4" or "4" or "H" => 4,
        _ => EccInvalid
    };

    /// <summary>
    /// 글자마다 어느 모드로 담을지 동적계획법으로 고른다. 비용은 글자 하나를 담는 값에 모드를 여닫는
    /// 값을 더한 것이고, 문자 모드는 부분집합이 바뀔 때마다 6비트가 더 든다.
    /// </summary>
    private static int[] DefineModes(byte[] data)
    {
        var charModes = new int[data.Length][];
        var previous = (int[])HeadCosts.Clone();
        var numericEnd = 0;
        var numericCost = 0;
        var textSubmode = 1;

        for (var i = 0; i < data.Length; i++)
        {
            var costs = new int[ModeCount];
            var picks = new int[ModeCount];
            Array.Fill(picks, ModeNone);
            charModes[i] = picks;

            bool text1;
            bool text2;
            if (InNumeric(data, i, ref numericEnd, ref numericCost))
            {
                costs[ModeNumeric] = previous[ModeNumeric] + numericCost;
                picks[ModeNumeric] = ModeNumeric;
                text1 = true;
                text2 = false;
            }
            else
            {
                text1 = LookupText1(data[i]) >= 0;
                text2 = LookupText2(data[i]) >= 0;
            }

            if (text1 || text2)
            {
                var switching = (textSubmode == 1 && text2) || (textSubmode == 2 && text1);
                costs[ModeText] = previous[ModeText] + (switching ? (6 + 6) * CostUnit : 6 * CostUnit);
                if (switching) textSubmode = text2 ? 2 : 1;
                picks[ModeText] = ModeText;
            }
            else
            {
                textSubmode = 1;
            }

            costs[ModeBinary] = previous[ModeBinary] + 8 * CostUnit;
            picks[ModeBinary] = ModeBinary;

            if (i == data.Length - 1)
            {
                for (var j = 0; j < ModeCount; j++)
                {
                    if (picks[j] != ModeNone) costs[j] += EodCosts[j];
                }
            }

            for (var to = 0; to < ModeCount; to++)
            {
                for (var from = 0; from < ModeCount; from++)
                {
                    if (to == from || picks[from] == ModeNone) continue;
                    var cost = costs[from] + SwitchCosts[from][to];
                    if (picks[to] != ModeNone && cost >= costs[to]) continue;
                    costs[to] = cost;
                    picks[to] = from;
                }
            }
            previous = costs;
        }

        var ending = 0;
        for (var j = 1; j < ModeCount; j++)
        {
            if (previous[j] < previous[ending]) ending = j;
        }

        var modes = new int[data.Length];
        for (var i = data.Length - 1; i >= 0; i--)
        {
            ending = charModes[i][ending];
            modes[i] = ending;
        }
        return modes;
    }

    /// <summary>
    /// 숫자 세 자리가 10비트에 함께 들어가므로, 이어지는 자릿수에 따라 글자당 값이 10·5·10/3비트로 갈린다.
    /// 한 번 센 묶음은 <paramref name="end"/>에 남겨 두고 그 안에서는 다시 세지 않는다.
    /// </summary>
    private static bool InNumeric(byte[] data, int position, ref int end, ref int cost)
    {
        if (position < end) return true;

        var i = position;
        while (i < data.Length && i < position + 3 && data[i] is >= (byte)'0' and <= (byte)'9') i++;
        if (i == position)
        {
            end = 0;
            return false;
        }

        end = i;
        cost = (i - position) switch
        {
            1 => 10 * CostUnit,
            2 => 5 * CostUnit,
            _ => 10 * CostUnit / 3
        };
        return true;
    }

    /// <summary>표 3. 숫자·영대문자·영소문자를 0~61에 담는 문자 1 부분집합.</summary>
    private static int LookupText1(byte c) => c switch
    {
        >= (byte)'0' and <= (byte)'9' => c - '0',
        >= (byte)'A' and <= (byte)'Z' => c - 'A' + 10,
        >= (byte)'a' and <= (byte)'z' => c - 'a' + 36,
        _ => -1
    };

    /// <summary>표 4. 제어 문자와 기호를 담는 문자 2 부분집합. 0x1C~0x1F는 빠져 있어 바이트 모드로 가야 한다.</summary>
    private static int LookupText2(byte c) => c switch
    {
        <= 27 => c,
        >= (byte)' ' and <= (byte)'/' => c - ' ' + 28,
        >= (byte)':' and <= (byte)'@' => c - ':' + 44,
        >= (byte)'[' and <= 96 => c - '[' + 51,
        >= (byte)'{' and <= 127 => c - '{' + 57,
        _ => -1
    };

    private static int Submode(byte c) => LookupText1(c) >= 0 ? 1 : 2;

    /// <summary>같은 모드가 이어지는 구간마다 지시자·자료·종료 부호를 붙인다(5.3).</summary>
    private static List<bool> BuildBitStream(byte[] data, int[] modes)
    {
        var bits = new List<bool>(data.Length * 8);
        var position = 0;
        while (position < data.Length)
        {
            var blockLength = 1;
            while (position + blockLength < data.Length && modes[position + blockLength] == modes[position])
                blockLength++;

            switch (modes[position])
            {
                case ModeNumeric:
                    AppendBits(bits, 1, 4);
                    var group = 0;
                    for (var i = 0; i < blockLength; i += group)
                    {
                        group = Math.Min(3, blockLength - i);
                        var value = 0;
                        for (var g = 0; g < group; g++)
                            value = value * 10 + (data[position + i + g] - '0');
                        AppendBits(bits, value, 10);
                    }
                    // 표 2. 마지막 묶음이 몇 자리였는지를 종료 부호 1021·1022·1023으로 알린다.
                    AppendBits(bits, 1020 + group, 10);
                    break;

                case ModeText:
                    AppendBits(bits, 2, 4);
                    var submode = 1;
                    for (var i = 0; i < blockLength; i++)
                    {
                        var c = data[position + i];
                        if (Submode(c) != submode)
                        {
                            AppendBits(bits, 62, 6);
                            submode = Submode(c);
                        }
                        AppendBits(bits, submode == 1 ? LookupText1(c) : LookupText2(c), 6);
                    }
                    AppendBits(bits, 63, 6);
                    break;

                default:
                    AppendBits(bits, 3, 4);
                    AppendBits(bits, blockLength, 13);
                    for (var i = 0; i < blockLength; i++)
                        AppendBits(bits, data[position + i], 8);
                    break;
            }
            position += blockLength;
        }
        return bits;
    }

    private static void AppendBits(List<bool> bits, int value, int length)
    {
        for (var b = length - 1; b >= 0; b--)
            bits.Add((value >> b & 1) != 0);
    }

    private static void AppendBits(bool[] bits, ref int position, int value, int length)
    {
        for (var b = length - 1; b >= 0; b--)
            bits[position++] = (value >> b & 1) != 0;
    }

    private static BitMatrix? Build(List<bool> bits, int wantVersion, int wantEcc, int wantMask)
    {
        var codewords = (bits.Count + 7) / 8;
        var ecc = wantEcc == EccAuto ? 1 : wantEcc;

        var version = 0;
        for (var v = MaxVersion; v >= 1; v--)
        {
            if (DataCodewords[ecc - 1][v - 1] >= codewords) version = v;
        }
        if (version == 0) return null;
        if (wantVersion != 0 && wantVersion < version) return null;
        if (wantVersion > version) version = wantVersion;

        // 자리가 남으면 등급을 올린다. 등급을 콕 집어 지정했을 때는 그대로 둔다.
        if (wantEcc == EccAuto)
        {
            while (ecc < 4 && codewords <= DataCodewords[ecc][version - 1]) ecc++;
        }

        var datastream = new byte[DataCodewords[ecc - 1][version - 1]];
        for (var i = 0; i < bits.Count; i++)
        {
            if (bits[i]) datastream[i >> 3] |= (byte)(0x80 >> (i & 7));
        }

        var size = version * 2 + 21;
        var (function, dark) = SetupGrid(size, version);
        FillData(function, dark, size, MakePicketFence(AddEcc(datastream, version, ecc)));

        var mask = wantMask >= 0 ? wantMask : PickMask(function, dark, size, version, ecc);
        ApplyMask(function, dark, size, mask);
        SetFunctionInfo(dark, size, version, ecc, mask);

        var matrix = new BitMatrix(size, size);
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (dark[y, x]) matrix[x, y] = true;
            }
        }
        return matrix;
    }

    /// <summary>
    /// 자료 부호어를 표 D1의 블록으로 잘라 각 블록 뒤에 오류정정 부호어를 붙인다.
    /// 자료가 모자라는 블록 꼬리는 0으로 채운다 — 메움 부호어를 따로 두지 않는 규격이다.
    /// </summary>
    private static byte[] AddEcc(byte[] datastream, int version, int ecc)
    {
        var fullstream = new byte[TotalCodewords[version - 1]];
        var tableBase = ((version - 1) * 4 + (ecc - 1)) * 9;
        var input = 0;
        var output = 0;

        for (var group = 0; group < 3; group++)
        {
            var batch = EccBlocks[tableBase + group * 3];
            if (batch == 0) continue;
            var dataLength = EccBlocks[tableBase + group * 3 + 1];
            var eccLength = EccBlocks[tableBase + group * 3 + 2];

            var solomon = new ReedSolomon(0x163, eccLength);
            var block = new byte[dataLength];
            for (var b = 0; b < batch; b++)
            {
                for (var j = 0; j < dataLength; j++)
                {
                    block[j] = input < datastream.Length ? datastream[input] : (byte)0;
                    fullstream[output++] = block[j];
                    input++;
                }
                foreach (var parity in solomon.Encode(block))
                    fullstream[output++] = parity;
            }
        }
        return fullstream;
    }

    /// <summary>5.8.2. 부호어를 13개 간격으로 다시 훑어 깐다. 한 군데가 뭉개져도 여러 블록에 흩어진다.</summary>
    private static byte[] MakePicketFence(byte[] fullstream)
    {
        var result = new byte[fullstream.Length];
        var output = 0;
        for (var start = 0; start < 13; start++)
        {
            for (var i = start; i < fullstream.Length; i += 13)
                result[output++] = fullstream[i];
        }
        return result;
    }

    /// <summary>찾기 무늬·분리 띠·구조 정보 자리·정렬 무늬를 깐다. 판형 3까지는 정렬 무늬가 없다.</summary>
    private static (bool[,] Function, bool[,] Dark) SetupGrid(int size, int version)
    {
        var function = new bool[size, size];
        var dark = new bool[size, size];

        PlaceFinder(function, dark, FinderTopLeft, 0, 0);
        PlaceFinder(function, dark, FinderCorner, 0, size - 7);
        PlaceFinder(function, dark, FinderCorner, size - 7, 0);
        PlaceFinder(function, dark, FinderBottomRight, size - 7, size - 7);

        for (var i = 0; i < 8; i++)
        {
            // 찾기 무늬를 감싸는 분리 띠. 네 귀퉁이가 같은 모양이라 가로·세로를 함께 긋는다.
            function[7, i] = true;
            function[i, 7] = true;
            function[7, size - i - 1] = true;
            function[size - i - 1, 7] = true;
            function[i, size - 8] = true;
            function[size - 8, i] = true;
            function[size - 8, size - i - 1] = true;
            function[size - i - 1, size - 8] = true;
        }

        for (var i = 0; i < 9; i++)
        {
            // 구조 정보 자리. 값은 마스크가 정해진 뒤에 채운다.
            function[8, i] = true;
            function[i, 8] = true;
            function[8, size - i - 1] = true;
            function[size - i - 1, 8] = true;
            function[i, size - 9] = true;
            function[size - 9, i] = true;
            function[size - 9, size - i - 1] = true;
            function[size - i - 1, size - 9] = true;
        }

        if (version > 3) PlaceAlignment(function, dark, size, version);
        return (function, dark);
    }

    private static void PlaceFinder(bool[,] function, bool[,] dark, int[] pattern, int x, int y)
    {
        for (var xp = 0; xp < 7; xp++)
        {
            for (var yp = 0; yp < 7; yp++)
            {
                function[y + yp, x + xp] = true;
                dark[y + yp, x + xp] = (pattern[yp] & (0x40 >> xp)) != 0;
            }
        }
    }

    /// <summary>부속서 A. 심볼을 k·r-1 간격의 격자로 나누고 교차점마다 정렬 무늬와 보조 무늬를 놓는다.</summary>
    private static void PlaceAlignment(bool[,] function, bool[,] dark, int size, int version)
    {
        var k = ModuleK[version - 1];
        var r = ModuleR[version - 1];
        var m = ModuleM[version - 1];

        var y = 0;
        var modY = 0;
        do
        {
            if ((modY & 1) == 0)
            {
                if ((m & 1) == 1) PlotAssistant(function, dark, size, 0, y);
            }
            else
            {
                if ((m & 1) == 0) PlotAssistant(function, dark, size, 0, y);
                PlotAssistant(function, dark, size, size - 1, y);
            }
            y += modY < m ? k : r - 1;
            modY++;
        } while (y < size);

        var x = size - 1;
        var modX = 0;
        do
        {
            if ((modX & 1) == 0)
            {
                if ((m & 1) == 1) PlotAssistant(function, dark, size, x, size - 1);
            }
            else
            {
                if ((m & 1) == 0) PlotAssistant(function, dark, size, x, size - 1);
                PlotAssistant(function, dark, size, x, 0);
            }
            x -= modX < m ? k : r - 1;
            modX++;
        } while (x >= 0);

        var columnSwitch = true;
        y = 0;
        modY = 0;
        do
        {
            var moduleHeight = modY < m ? k : r - 1;
            var rowSwitch = columnSwitch;
            columnSwitch = !columnSwitch;

            x = size - 1;
            modX = 0;
            do
            {
                var moduleWidth = modX < m ? k : r - 1;
                // 격자점은 한 칸 걸러 하나씩만 쓴다. 좌상단 교차점은 찾기 무늬와 겹쳐 건너뛴다.
                if (rowSwitch && !(y == 0 && x == size - 1))
                    PlotAlignment(function, dark, size, x, y, moduleWidth, moduleHeight);
                rowSwitch = !rowSwitch;
                x -= moduleWidth;
                modX++;
            } while (x >= 0);

            y += moduleHeight;
            modY++;
        } while (y < size);
    }

    /// <summary>정렬 무늬. 교차점에서 왼쪽·아래로 뻗는 어두운 선과 그 바깥의 밝은 선 한 줄이다.</summary>
    private static void PlotAlignment(bool[,] function, bool[,] dark, int size, int x, int y, int w, int h)
    {
        SafePlot(function, dark, size, x, y, true);
        SafePlot(function, dark, size, x - 1, y + 1, false);

        for (var i = 1; i <= w; i++)
        {
            SafePlot(function, dark, size, x - i, y, true);
            SafePlot(function, dark, size, x - i - 1, y + 1, false);
        }
        for (var i = 1; i < h; i++)
        {
            SafePlot(function, dark, size, x, y + i, true);
            SafePlot(function, dark, size, x - 1, y + i + 1, false);
        }
    }

    /// <summary>보조 정렬 무늬. 가장자리에 놓이는 3×3 십자 점이다.</summary>
    private static void PlotAssistant(bool[,] function, bool[,] dark, int size, int x, int y)
    {
        for (var dy = -1; dy <= 1; dy++)
        {
            for (var dx = -1; dx <= 1; dx++)
                SafePlot(function, dark, size, x + dx, y + dy, dx == 0 && dy == 0);
        }
    }

    /// <summary>이미 자리를 차지한 기능 모듈은 덮지 않는다.</summary>
    private static void SafePlot(bool[,] function, bool[,] dark, int size, int x, int y, bool value)
    {
        if (x < 0 || x >= size || y < 0 || y >= size) return;
        if (function[y, x]) return;
        function[y, x] = true;
        dark[y, x] = value;
    }

    /// <summary>기능 모듈을 뺀 나머지를 왼쪽 위부터 행 단위로 채운다.</summary>
    private static void FillData(bool[,] function, bool[,] dark, int size, byte[] stream)
    {
        var bit = 0;
        var limit = stream.Length * 8;
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (function[y, x]) continue;
                if (bit >= limit) return;
                if ((stream[bit >> 3] & (0x80 >> (bit & 7))) != 0) dark[y, x] = true;
                bit++;
            }
        }
    }

    /// <summary>
    /// 구조 정보 34비트: 판형+20(8비트)·등급-1(2비트)·마스크(2비트)에 GF(16) 오류정정 네 니블을 붙인다.
    /// 부호어는 28비트에서 끝나고 남는 여섯 자리는 GB/T 21049가 010101로 채우게 한 자리다.
    /// ISO/IEC 20830:2021은 그림 1·부속서 K에서 이 자리를 비워 두지만 그림 2와 4~9에는 그대로 있고,
    /// 복호에 쓰이지 않는 자리라 어느 쪽이든 읽힌다 — 애니라벨과 같게 채운다.
    /// </summary>
    private static void SetFunctionInfo(bool[,] dark, int size, int version, int ecc, int mask)
    {
        var info = new bool[34];
        var position = 0;
        AppendBits(info, ref position, version + 20, 8);
        AppendBits(info, ref position, ecc - 1, 2);
        AppendBits(info, ref position, mask, 2);

        var words = new byte[3];
        for (var i = 0; i < 3; i++)
        {
            for (var j = 0; j < 4; j++)
            {
                if (info[i * 4 + j]) words[i] |= (byte)(0x08 >> j);
            }
        }
        foreach (var nibble in new ReedSolomon(0x13, 4).Encode(words))
            AppendBits(info, ref position, nibble, 4);
        for (var i = position; i < info.Length; i++)
            info[i] = (i & 1) == 1;

        // 네 귀퉁이에 같은 값을 두 벌씩 흩어 놓아 한 귀퉁이가 가려져도 읽힌다.
        for (var i = 0; i < 9; i++)
        {
            if (info[i])
            {
                dark[8, i] = true;
                dark[size - 9, size - i - 1] = true;
            }
            if (info[i + 8])
            {
                dark[8 - i, 8] = true;
                dark[size - 9 + i, size - 9] = true;
            }
            if (info[i + 17])
            {
                dark[i, size - 9] = true;
                dark[size - 1 - i, 8] = true;
            }
            if (info[i + 25])
            {
                dark[8, size - 9 + i] = true;
                dark[size - 9, 8 - i] = true;
            }
        }
    }

    /// <summary>표 8의 마스크 넷. 0번은 마스크 없음이고, 행·열 번호는 1부터 센다.</summary>
    private static bool MaskBit(int mask, int row, int col)
    {
        var i = row + 1;
        var j = col + 1;
        return mask switch
        {
            1 => ((i + j) & 1) == 0,
            2 => (((i + j) % 3 + j % 3) & 1) == 0,
            3 => ((i % j + j % i + i % 3 + j % 3) & 1) == 0,
            _ => false
        };
    }

    private static void ApplyMask(bool[,] function, bool[,] dark, int size, int mask)
    {
        if (mask == 0) return;
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (!function[y, x] && MaskBit(mask, y, x)) dark[y, x] ^= true;
            }
        }
    }

    /// <summary>벌점이 가장 낮은 마스크. 구조 정보까지 넣고 재야 값이 맞는다.</summary>
    private static int PickMask(bool[,] function, bool[,] dark, int size, int version, int ecc)
    {
        var best = 0;
        var bestPenalty = int.MaxValue;
        for (var mask = 0; mask < 4; mask++)
        {
            var trial = (bool[,])dark.Clone();
            ApplyMask(function, trial, size, mask);
            SetFunctionInfo(trial, size, version, ecc, mask);

            var penalty = Evaluate(trial, size);
            if (penalty >= bestPenalty) continue;
            bestPenalty = penalty;
            best = mask;
        }
        return best;
    }

    /// <summary>
    /// 표 9. 찾기 무늬와 헷갈리는 1:1:1:1:3 비율이 밝은 3모듈과 맞닿으면 50점, 같은 색이 3모듈 이상
    /// 이어지면 길이×4점. AIMD-15는 i를 행 번호로 적었지만 ISO/IEC 20830 5.8.4.3에서 같은 색 모듈
    /// 수로 바로잡혔다.
    /// </summary>
    private static int Evaluate(bool[,] cells, int size)
    {
        var result = 0;

        for (var x = 0; x < size; x++)
        {
            for (var y = 0; y <= size - 7; y++)
            {
                if (!MatchesFinderRatio(cells, y, x, 1, 0)) continue;
                if (IsQuiet(cells, size, x, y - 1, 0, -1) || IsQuiet(cells, size, x, y + 7, 0, 1))
                    result += 50;
                y++;
            }
        }

        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x <= size - 7; x++)
            {
                if (!MatchesFinderRatio(cells, y, x, 0, 1)) continue;
                if (IsQuiet(cells, size, x - 1, y, -1, 0) || IsQuiet(cells, size, x + 7, y, 1, 0))
                    result += 50;
                x++;
            }
        }

        for (var line = 0; line < size; line++)
        {
            result += RunPenalty(cells, size, line, true);
            result += RunPenalty(cells, size, line, false);
        }
        return result;
    }

    /// <summary>1011101·1010111을 뒤집은 두 무늬가 찾기 무늬의 1:1:1:1:3 비율이다.</summary>
    private static bool MatchesFinderRatio(bool[,] cells, int row, int col, int dRow, int dCol)
    {
        var bits = 0;
        for (var i = 0; i < 7; i++)
            bits = bits << 1 | (cells[row + i * dRow, col + i * dCol] ? 1 : 0);
        return bits is 0b1010111 or 0b1110101;
    }

    /// <summary>무늬 바깥으로 밝은 모듈이 세 개 이어지는지 본다. 심볼 밖은 밝은 것으로 친다.</summary>
    private static bool IsQuiet(bool[,] cells, int size, int x, int y, int dx, int dy)
    {
        for (var step = 0; step < 3; step++)
        {
            if (x < 0 || x >= size || y < 0 || y >= size) return true;
            if (cells[y, x]) return false;
            x += dx;
            y += dy;
        }
        return true;
    }

    /// <summary>한 줄에서 같은 색이 3모듈 이상 이어질 때마다 길이×4점. 줄 앞은 밝은 것으로 놓고 센다.</summary>
    private static int RunPenalty(bool[,] cells, int size, int line, bool vertical)
    {
        var result = 0;
        var block = 0;
        var state = false;
        for (var i = 0; i < size; i++)
        {
            var cell = vertical ? cells[i, line] : cells[line, i];
            if (cell == state)
            {
                block++;
                continue;
            }
            if (block >= 3) result += block * 4;
            block = 1;
            state = cell;
        }
        return block >= 3 ? result + block * 4 : result;
    }

    /// <summary>
    /// zint reedsol.c의 부호기. 자료 부호어는 GF(256)·원시 다항식 0x163(x⁸+x⁶+x⁵+x+1),
    /// 구조 정보는 GF(16)·0x13(x⁴+x+1)을 쓰고 생성 다항식의 첫 근은 둘 다 α¹이다.
    /// </summary>
    private sealed class ReedSolomon
    {
        private readonly int[] _log;
        private readonly int[] _alog;
        private readonly int[] _poly;
        private readonly int[] _logPoly;
        private readonly int _count;

        public ReedSolomon(int primePoly, int count)
        {
            var degree = 0;
            while (primePoly >> (degree + 1) != 0) degree++;
            var order = 1 << degree;
            var logmod = order - 1;

            _log = new int[order];
            _alog = new int[logmod * 2];
            var power = 1;
            for (var v = 0; v < logmod; v++)
            {
                // 지수표를 두 벌 깔아 두면 지수를 더할 때 나머지 연산이 필요 없다.
                _alog[v] = power;
                _alog[logmod + v] = power;
                _log[power] = v;
                power <<= 1;
                if ((power & order) != 0) power ^= primePoly;
            }

            _count = count;
            _poly = new int[count + 1];
            _logPoly = new int[count + 1];
            _poly[0] = 1;
            var index = 1;
            for (var i = 1; i <= count; i++)
            {
                _poly[i] = 1;
                for (var k = i - 1; k > 0; k--)
                {
                    if (_poly[k] != 0) _poly[k] = _alog[_log[_poly[k]] + index];
                    _poly[k] ^= _poly[k - 1];
                }
                _poly[0] = _alog[_log[_poly[0]] + index];
                index++;
            }
            for (var i = 0; i <= count; i++)
                _logPoly[i] = _log[_poly[i]];
        }

        public byte[] Encode(byte[] data)
        {
            var remainder = new int[_count];
            foreach (var word in data)
            {
                var factor = remainder[_count - 1] ^ word;
                if (factor == 0)
                {
                    Array.Copy(remainder, 0, remainder, 1, _count - 1);
                    remainder[0] = 0;
                    continue;
                }
                var logFactor = _log[factor];
                for (var k = _count - 1; k > 0; k--)
                {
                    remainder[k] = _poly[k] != 0
                        ? remainder[k - 1] ^ _alog[logFactor + _logPoly[k]]
                        : remainder[k - 1];
                }
                remainder[0] = _alog[logFactor + _logPoly[0]];
            }

            var parity = new byte[_count];
            for (var i = 0; i < _count; i++)
                parity[i] = (byte)remainder[_count - 1 - i];
            return parity;
        }
    }
}
