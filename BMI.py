def calculate(weight,height):
    """
    return float indicating BMI
    (body mass index)
    given weight in pounds (float)
    given height in inches (float)
    """
    return 703.0695 * float(weight) / (height ** 2)
